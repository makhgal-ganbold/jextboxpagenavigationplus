<?php
/**
 * @package    "JExtBOX Page Navigation Plus" plugin for Joomla!
 * @reference  plg_content_pagenavigation - the core plugin of Joomla CMS
 * @copyright  Copyright (c) 2021-2026 Makhgal Ganbold
 * @author     Makhgal Ganbold
 * @link       https://www.jextbox.com
 * @license    GNU/GPL License - https://www.gnu.org/licenses/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$this->loadLanguage();
$lang = $this->getLanguage();

?>

<nav class="pagenavigation">
	<span class="pagination ms-0">
		<?php if ($row->prev) : ?>
			<?php $direction = $lang->isRtl() ? 'right' : 'left'; ?>
			<a class="btn btn-sm btn-secondary previous" href="<?php echo Route::_($row->prev->link); ?>" rel="prev">
			<span class="visually-hidden">
				<?php echo $row->prev->title; ?>
			</span>
			<?php echo '<span class="icon-chevron-' . $direction . '" aria-hidden="true"></span> <span aria-hidden="true">' . $row->prev->label . '</span>'; ?>
			</a>
		<?php endif; ?>
		<?php if ($row->next) : ?>
			<?php $direction = $lang->isRtl() ? 'left' : 'right'; ?>
			<a class="btn btn-sm btn-secondary next" href="<?php echo Route::_($row->next->link); ?>" rel="next">
			<span class="visually-hidden">
				<?php echo $row->next->title; ?>
			</span>
			<?php echo '<span aria-hidden="true">' . $row->next->label . '</span> <span class="icon-chevron-' . $direction . '" aria-hidden="true"></span>'; ?>
			</a>
		<?php endif; ?>
	</span>
</nav>
