(function () {
  const cfg = window.mintLmsNativeEditor || {};
  const postId = parseInt(cfg.postId, 10) || 0;
  const nonce = cfg.discardNonce || '';
  const ajaxUrl = cfg.ajaxUrl || '';

  let preserve = false;
  let lastRevealedStatus = '';

  function lockAutosave() {
    if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
      return false;
    }
    const editor = wp.data.dispatch('core/editor');
    if (!editor || typeof editor.lockPostAutosaving !== 'function') {
      return false;
    }
    editor.lockPostAutosaving('mintlms');
    return true;
  }

  function armLock() {
    if (lockAutosave()) {
      return;
    }
    if (!window.wp || !wp.data || typeof wp.data.subscribe !== 'function') {
      return;
    }
    const unsub = wp.data.subscribe(function () {
      if (lockAutosave()) {
        unsub();
      }
    });
  }

  if (window.wp && typeof wp.domReady === 'function') {
    wp.domReady(armLock);
  } else {
    armLock();
  }

  function isPreserveUrl(href) {
    if (!href) {
      return false;
    }
    return (
      href.indexOf('mint-lms-builder') !== -1 ||
      href.indexOf('mint-lms-lesson-edit') !== -1 ||
      href.indexOf('mint-lms-edit-quiz') !== -1 ||
      href.indexOf('mint-lms-course-edit') !== -1
    );
  }

  document.addEventListener(
    'click',
    function (event) {
      const link = event.target && event.target.closest ? event.target.closest('a') : null;
      if (link && isPreserveUrl(link.href)) {
        preserve = true;
      }
    },
    true
  );

  document.addEventListener(
    'submit',
    function () {
      preserve = true;
    },
    true
  );

  function currentStatus() {
    try {
      const status = wp.data.select('core/editor').getCurrentPostAttribute('status');
      if (typeof status === 'string' && status !== '') {
        return status;
      }
    } catch (err) {
      // Editor store not ready; fall back to the PHP-provided status.
    }
    return cfg.status || '';
  }

  function currentPostId() {
    try {
      const id = wp.data.select('core/editor').getCurrentPostId();
      const n = parseInt(id, 10);
      if (n > 0) {
        return n;
      }
    } catch (err) {
      // Fall through.
    }
    return postId;
  }

  function builderUrlFor(id) {
    const templates = cfg.builderUrls || {};
    const postType = cfg.postType || '';
    const template = templates[postType] || '';
    if (!template || id <= 0) {
      return '';
    }
    return String(template).split('__ID__').join(String(id));
  }

  /**
   * After Save draft / Publish, reveal Open In Mint LMS Builder without a page refresh.
   */
  function revealBuilderGates() {
    const status = currentStatus();
    if (!status || status === 'auto-draft') {
      return;
    }
    lastRevealedStatus = status;

    const id = currentPostId();
    const liveUrl = builderUrlFor(id);
    document.querySelectorAll('[data-mintlms-builder-gate]').forEach(function (gate) {
      const pending = gate.querySelector('.mintlms-builder-gate__pending');
      const button = gate.querySelector('.mintlms-builder-gate__button');
      if (pending) {
        pending.hidden = true;
      }
      if (!button) {
        return;
      }
      button.hidden = false;
      if (liveUrl) {
        button.href = liveUrl;
        button.setAttribute('data-url', liveUrl);
      } else {
        const fallback = button.getAttribute('data-url') || button.getAttribute('href') || '';
        if (fallback) {
          button.href = fallback;
        }
      }
    });
  }

  function armBuilderReveal() {
    revealBuilderGates();
    if (!window.wp || !wp.data || typeof wp.data.subscribe !== 'function') {
      return;
    }
    let wasSaving = false;
    wp.data.subscribe(function () {
      let saving = false;
      try {
        saving = !!wp.data.select('core/editor').isSavingPost();
      } catch (err) {
        saving = false;
      }
      // Reveal as soon as status leaves auto-draft, and again when a save finishes.
      const status = currentStatus();
      if (status && status !== 'auto-draft') {
        revealBuilderGates();
      }
      if (wasSaving && !saving) {
        revealBuilderGates();
      }
      wasSaving = saving;
    });
  }

  if (window.wp && typeof wp.domReady === 'function') {
    wp.domReady(armBuilderReveal);
  } else {
    armBuilderReveal();
  }

  function discard() {
    if (preserve || postId <= 0 || !ajaxUrl || !nonce) {
      return;
    }
    if (currentStatus() !== 'auto-draft') {
      return;
    }
    const body = new FormData();
    body.append('action', 'mintlms_discard_auto_draft');
    body.append('nonce', nonce);
    body.append('post_id', String(postId));
    if (typeof navigator.sendBeacon === 'function') {
      navigator.sendBeacon(ajaxUrl, body);
      return;
    }
    window.fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true });
  }

  window.addEventListener('pagehide', discard);
})();
