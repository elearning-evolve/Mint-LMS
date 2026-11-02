<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$columns    = $columns ?? array();
$rows       = $rows ?? array();
$pagination = $pagination ?? array();
$empty      = $empty ?? '';
$attrs      = $attrs ?? '';

$currentPage = (int) ( $pagination['current'] ?? 1 );
$totalPages  = (int) ( $pagination['total'] ?? 1 );
$baseUrl     = $pagination['base_url'] ?? '';
?>
<div class="mint-overflow-hidden mint-bg-neutral-50 mint-border mint-border-neutral-200 mint-rounded-lg mint-shadow-sm" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="mint-overflow-x-auto">
		<table class="mint-min-w-full mint-divide-y mint-divide-neutral-200">
			<thead class="mint-bg-neutral-100">
				<tr>
					<?php foreach ( $columns as $column ) : ?>
						<?php
						$colLabel = is_array( $column ) ? ( $column['label'] ?? '' ) : (string) $column;
						$colClass = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
						?>
						<th scope="col" class="mint-px-4 mint-py-3 mint-text-left mint-text-xs mint-font-medium mint-text-neutral-500 mint-uppercase mint-tracking-wide <?php echo esc_attr( $colClass ); ?>">
							<?php echo esc_html( $colLabel ); ?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody class="mint-divide-y mint-divide-neutral-200">
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="<?php echo esc_attr( (string) max( 1, count( $columns ) ) ); ?>" class="mint-p-0">
							<?php echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="hover:mint-bg-neutral-50">
							<?php foreach ( $columns as $columnKey => $column ) : ?>
								<?php
								$key   = is_string( $columnKey ) ? $columnKey : ( is_array( $column ) ? ( $column['key'] ?? '' ) : '' );
								$cell  = is_array( $row ) ? ( $row[ $key ] ?? '' ) : '';
								$class = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
								?>
								<td class="mint-px-4 mint-py-3 mint-text-sm mint-text-neutral-700 <?php echo esc_attr( $class ); ?>">
									<?php echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Row cells are pre-rendered HTML. ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php if ( $totalPages > 1 ) : ?>
		<div class="mint-flex mint-items-center mint-justify-between mint-px-4 mint-py-3 mint-border-t mint-border-neutral-200 mint-bg-neutral-50">
			<p class="mint-text-sm mint-text-neutral-500">
				<?php
				printf(
					/* translators: 1: current page number, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'mint-lms' ),
					absint( $currentPage ),
					absint( $totalPages )
				);
				?>
			</p>
			<div class="mint-flex mint-gap-2">
				<?php if ( $currentPage > 1 ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'paged', $currentPage - 1, $baseUrl ) ); ?>"
						class="mint-px-3 mint-py-1 mint-text-sm mint-text-neutral-700 mint-bg-neutral-100 mint-border mint-border-neutral-300 mint-rounded-md hover:mint-bg-neutral-200"
					>
						<?php echo esc_html__( 'Previous', 'mint-lms' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $currentPage < $totalPages ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'paged', $currentPage + 1, $baseUrl ) ); ?>"
						class="mint-px-3 mint-py-1 mint-text-sm mint-text-neutral-700 mint-bg-neutral-100 mint-border mint-border-neutral-300 mint-rounded-md hover:mint-bg-neutral-200"
					>
						<?php echo esc_html__( 'Next', 'mint-lms' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
