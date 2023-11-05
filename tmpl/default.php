<?php
/**
 * @package    "JExtBOX Page Navigation Plus" plugin for Joomla!
 * @reference  plg_content_pagenavigation - the core plugin of Joomla CMS
 * @copyright  Copyright (c) 2021-2023 Galaa
 * @author     Galaa
 * @link       www.jextbox.com
 * @license    GNU/GPL License - https://www.gnu.org/licenses/gpl.html
 */

defined('_JEXEC') or die;

?>

<?php if ($joomla_3) : ?>
	<ul class="pager pagenav">
		<?php if ($row->prev) :
			if (!empty($row->prev->direction))
				$direction = $row->prev->direction;
			else
				$direction = $langisRtl ? 'right' : 'left'; ?>
			<li class="previous">
				<a class="hasTooltip" title="<?php echo htmlspecialchars($row->prev->title); ?>" aria-label="<?php echo $row->prev->title_; ?>" href="<?php echo $row->prev->link; ?>" rel="prev">
					<?php echo '<span class="icon-chevron-' . $direction . '" aria-hidden="true"></span> <span aria-hidden="true">' . $row->prev->label . '</span>'; ?>
				</a>
			</li>
		<?php endif; ?>
		<?php if ($row->next) :
			if (!empty($row->next->direction))
				$direction = $row->next->direction;
			else
				$direction = $langisRtl ? 'left' : 'right'; ?>
			<li class="next">
				<a class="hasTooltip" title="<?php echo htmlspecialchars($row->next->title); ?>" aria-label="<?php echo $row->next->title_; ?>" href="<?php echo $row->next->link; ?>" rel="next">
					<?php echo '<span aria-hidden="true">' . $row->next->label . '</span> <span class="icon-chevron-' . $direction . '" aria-hidden="true"></span>'; ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
<?php else: ?>
	<nav class="pagenavigation">
		<ul class="pagination ms-0">
			<?php if ($row->prev) :
				if (!empty($row->prev->direction))
					$direction = $row->prev->direction;
				else
					$direction = $langisRtl ? 'right' : 'left'; ?>
				<li class="previous page-item">
					<a class="page-link" href="<?php echo $row->prev->link; ?>" rel="prev">
					<span class="visually-hidden">
						<?php echo $row->prev->title; ?>
					</span>
					<?php echo '<span class="icon-chevron-' . $direction . '" aria-hidden="true"></span> <span aria-hidden="true">' . $row->prev->label . '</span>'; ?>
					</a>
				</li>
			<?php endif; ?>
			<?php if ($row->next) :
				if (!empty($row->next->direction))
					$direction = $row->next->direction;
				else
					$direction = $langisRtl ? 'left' : 'right'; ?>
				<li class="next page-item">
					<a class="page-link" href="<?php echo $row->next->link; ?>" rel="next">
					<span class="visually-hidden">
						<?php echo $row->next->title; ?>
					</span>
					<?php echo '<span aria-hidden="true">' . $row->next->label . '</span> <span class="icon-chevron-' . $direction . '" aria-hidden="true"></span>'; ?>
					</a>
				</li>
			<?php endif; ?>
		</ul>
	</nav>
<?php endif; ?>
