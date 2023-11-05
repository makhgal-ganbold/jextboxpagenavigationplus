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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/**
 * Pagenavigation plugin class.
 *
 * @since  1.5
 */
class PlgContentJextboxPageNavigationPlus extends CMSPlugin
{

	/**
	 * If in the article view and the parameter is enabled shows the page navigation
	 *
	 * @param   string   $context  The context of the content being passed to the plugin
	 * @param   object   &$row     The article object
	 * @param   mixed    &$params  The article params
	 * @param   integer  $page     The 'page' number
	 *
	 * @return  mixed  void or true
	 *
	 * @since   1.6
	 */
	public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
	{

		$app   = Factory::getApplication();
		$view  = $app->input->get('view');
		$print = $app->input->getBool('print');

		if ($print)
		{
			return false;
		}

		if ($context === 'com_content.article' && $view === 'article' && $params->get('show_item_navigation'))
		{
			$joomla_3 = version_compare(JVERSION, '4.0.0', '<');
			$db         = Factory::getDbo();
			$user       = $joomla_3 ? JFactory::getUser() : Factory::getApplication()->getIdentity();
			$lang       = Factory::getLanguage();
			if ($joomla_3)
				$nullDate = $db->getNullDate();
			$now        = Factory::getDate()->toSql();
			$query      = $db->getQuery(true);
			$uid        = $row->id;
			$option     = 'com_content';
			$canPublish = $user->authorise('core.edit.state', $option . '.article.' . $row->id);

			/**
			 * The following is needed as different menu items types utilise a different param to control ordering.
			 * For Blogs the `orderby_sec` param is the order controlling param.
			 * For Table and List views it is the `orderby` param.
			 */
			$params_list = $params->toArray();

			if (array_key_exists('orderby_sec', $params_list))
			{
				$order_method = $params->get('orderby_sec', '');
			}
			else
			{
				$order_method = $params->get('orderby', '');
			}

			// Additional check for invalid sort ordering.
			if ($order_method === 'front')
			{
				$order_method = '';
			}

			if ($joomla_3) // Joomla 3
			{
				// Get the order code
				$orderDate = $params->get('order_date');
				$queryDate = $this->getQueryDate($orderDate);

				// Determine sort order.
				switch ($order_method)
				{
					case 'date' :
						$orderby = $queryDate;
						break;
					case 'rdate' :
						$orderby = $queryDate . ' DESC ';
						break;
					case 'alpha' :
						$orderby = 'a.title';
						break;
					case 'ralpha' :
						$orderby = 'a.title DESC';
						break;
					case 'hits' :
						$orderby = 'a.hits';
						break;
					case 'rhits' :
						$orderby = 'a.hits DESC';
						break;
					case 'order' :
						$orderby = 'a.ordering';
						break;
					case 'author' :
						$orderby = 'a.created_by_alias, u.name';
						break;
					case 'rauthor' :
						$orderby = 'a.created_by_alias DESC, u.name DESC';
						break;
					case 'front' :
						$orderby = 'f.ordering';
						break;
					default :
						$orderby = 'a.ordering';
						break;
				}

				$xwhere = ' AND (a.state = 1 OR a.state = -1)'
					. ' AND (publish_up = ' . $db->quote($nullDate) . ' OR publish_up <= ' . $db->quote($now) . ')'
					. ' AND (publish_down = ' . $db->quote($nullDate) . ' OR publish_down >= ' . $db->quote($now) . ')';

				// Sqlsrv changes
				$case_when = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0');
				$a_id = $query->castAsChar('a.id');
				$case_when .= ' THEN ' . $query->concatenate(array($a_id, 'a.alias'), ':');
				$case_when .= ' ELSE ' . $a_id . ' END as slug';

				$case_when1 = ' CASE WHEN ' . $query->charLength('cc.alias', '!=', '0');
				$c_id = $query->castAsChar('cc.id');
				$case_when1 .= ' THEN ' . $query->concatenate(array($c_id, 'cc.alias'), ':');
				$case_when1 .= ' ELSE ' . $c_id . ' END as catslug';
				$query->select('a.id, a.title, a.catid, a.language,' . $case_when . ',' . $case_when1)
					->from('#__content AS a')
					->join('LEFT', '#__categories AS cc ON cc.id = a.catid');

				if ($order_method === 'author' || $order_method === 'rauthor')
				{
					$query->select('a.created_by, u.name');
					$query->join('LEFT', '#__users AS u ON u.id = a.created_by');
				}

				$query->where(
						'a.catid = ' . (int) $row->catid . ' AND a.state = ' . (int) $row->state
							. ($canPublish ? '' : ' AND a.access IN (' . implode(',', Access::getAuthorisedViewLevels($user->id)) . ') ') . $xwhere
					);
				$query->order($orderby);

				if ($app->isClient('site') && $app->getLanguageFilter())
				{
					$query->where('a.language in (' . $db->quote($lang->getTag()) . ',' . $db->quote('*') . ')');
				}
			}
			else  // Joomla 4
			{
				if (in_array($order_method, ['date', 'rdate']))
				{
					// Get the order code
					$orderDate = $params->get('order_date');

					switch ($orderDate)
					{
						// Use created if modified is not set
						case 'modified':
							$orderby = 'CASE WHEN ' . $db->quoteName('a.modified') . ' IS NULL THEN ' .
								$db->quoteName('a.created') . ' ELSE ' . $db->quoteName('a.modified') . ' END';
							break;

						// Use created if publish_up is not set
						case 'published':
							$orderby = 'CASE WHEN ' . $db->quoteName('a.publish_up') . ' IS NULL THEN ' .
								$db->quoteName('a.created') . ' ELSE ' . $db->quoteName('a.publish_up') . ' END';
							break;

						// Use created as default
						default :
							$orderby = $db->quoteName('a.created');
							break;
					}

					if ($order_method === 'rdate')
					{
						$orderby .= ' DESC';
					}
				}
				else
				{
					// Determine sort order.
					switch ($order_method)
					{
						case 'alpha':
							$orderby = $db->quoteName('a.title');
							break;
						case 'ralpha':
							$orderby = $db->quoteName('a.title') . ' DESC';
							break;
						case 'hits':
							$orderby = $db->quoteName('a.hits');
							break;
						case 'rhits':
							$orderby = $db->quoteName('a.hits') . ' DESC';
							break;
						case 'author':
							$orderby = $db->quoteName(['a.created_by_alias', 'u.name']);
							break;
						case 'rauthor':
							$orderby = $db->quoteName('a.created_by_alias') . ' DESC, ' .
								$db->quoteName('u.name') . ' DESC';
							break;
						case 'front':
							$orderby = $db->quoteName('f.ordering');
							break;
						default:
							$orderby = $db->quoteName('a.ordering');
							break;
					}
				}

				$query->order($orderby);

				$case_when = ' CASE WHEN ' . $query->charLength($db->quoteName('a.alias'), '!=', '0')
					. ' THEN ' . $query->concatenate([$query->castAsChar($db->quoteName('a.id')), $db->quoteName('a.alias')], ':')
					. ' ELSE ' . $query->castAsChar('a.id') . ' END AS ' . $db->quoteName('slug');

				$case_when1 = ' CASE WHEN ' . $query->charLength($db->quoteName('cc.alias'), '!=', '0')
					. ' THEN ' . $query->concatenate([$query->castAsChar($db->quoteName('cc.id')), $db->quoteName('cc.alias')], ':')
					. ' ELSE ' . $query->castAsChar('cc.id') . ' END AS ' . $db->quoteName('catslug');

				$query->select($db->quoteName(['a.id', 'a.title', 'a.catid', 'a.language']))
					->select([$case_when, $case_when1])
					->from($db->quoteName('#__content', 'a'))
					->join('LEFT', $db->quoteName('#__categories', 'cc'), $db->quoteName('cc.id') . ' = ' . $db->quoteName('a.catid'));

				if ($order_method === 'author' || $order_method === 'rauthor')
				{
					$query->select($db->quoteName(['a.created_by', 'u.name']));
					$query->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));
				}

				$query->where(
					[
						$db->quoteName('a.catid') . ' = :catid',
						$db->quoteName('a.state') . ' = :state',
					]
				)
					->bind(':catid', $row->catid)
					->bind(':state', $row->state);

				if (!$canPublish)
				{
					$query->whereIn($db->quoteName('a.access'), Access::getAuthorisedViewLevels($user->id));
				}

				$query->where(
					[
						'(' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' <= :nowDate1)',
						'(' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' >= :nowDate2)',
					]
				)
					->bind(':nowDate1', $now)
					->bind(':nowDate2', $now);

				if ($app->isClient('site') && $app->getLanguageFilter())
				{
					$query->whereIn($db->quoteName('a.language'), [$lang->getTag(), '*']);
				}
			}

			$db->setQuery($query);
			$list = $db->loadObjectList('id');

			// This check needed if incorrect Itemid is given resulting in an incorrect result.
			if (!is_array($list))
			{
				$list = [];
			}

			reset($list);

			// Location of current content item in array list.
			$location = array_search($uid, array_keys($list));
			$rows     = array_values($list);

			$row->prev = null;
			$row->next = null;

			if ($location - 1 >= 0)
			{
				// The previous content item cannot be in the array position -1.
				$row->prev = $rows[$location - 1];
			}

			if (($location + 1) < count($rows))
			{
				// The next content item cannot be in an array position greater than the number of array postions.
				$row->next = $rows[$location + 1];
			}

			if ($row->prev)
			{
				$row->prev->label = ($this->params->get('display', 0) == 0) ? Text::_('JPREV') : $this->truncate_title($row->prev->title);
				if ($joomla_3)
					$row->prev->link = JRoute::_(ContentHelperRoute::getArticleRoute($row->prev->slug, $row->prev->catid, $row->prev->language));
				else
					$row->prev->link = RouteHelper::getArticleRoute($row->prev->slug, $row->prev->catid, $row->prev->language);
			}

			if ($row->next)
			{
				$row->next->label = ($this->params->get('display', 0) == 0) ? Text::_('JNEXT') : $this->truncate_title($row->next->title);
				if ($joomla_3)
					$row->next->link = JRoute::_(ContentHelperRoute::getArticleRoute($row->next->slug, $row->next->catid, $row->next->language));
				else
					$row->next->link = RouteHelper::getArticleRoute($row->next->slug, $row->next->catid, $row->next->language);
			}

			// Output.
			if ($row->prev || $row->next)
			{
				if ($this->params->get('show_parent', 1))
				{
					if (empty($row->prev))
					{
						$row->prev = new StdClass;
						$row->prev->link = $this->parent_link($row->catid, $joomla_3);
						$row->prev->label = $this->parent_label($row->category_title);
						$row->prev->title = $this->parent_label($row->category_title);
						$row->prev->direction = 'up';
					}
					if (empty($row->next))
					{
						$row->next = new StdClass;
						$row->next->link = $this->parent_link($row->catid, $joomla_3);
						$row->next->label = $this->parent_label($row->category_title);
						$row->next->title = $this->parent_label($row->category_title);
						$row->next->direction = 'up';
					}
				}

				if ($joomla_3) // Joomla 3
				{
					if (!empty($row->prev->title))
						$row->prev->title_ = JText::sprintf('JPREVIOUS_TITLE', htmlspecialchars($row->prev->title));
					if (!empty($row->next->title))
						$row->next->title_ = JText::sprintf('JNEXT_TITLE', htmlspecialchars($row->next->title));
				}

				if (!$this->params->get('direction_between_prev_next', 1))
				{
					$tmp = $row->prev;
					$row->prev = $row->next;
					$row->next = $tmp;
					unset($tmp);
				}

				$langisRtl = Factory::getLanguage()->isRtl();

				// Get the path for the layout file
				$path = PluginHelper::getLayoutPath('content', 'jextboxpagenavigationplus');

				// Render the pagenav
				ob_start();
				include $path;
				$row->pagination = ob_get_clean();

				$row->paginationposition = $this->params->get('position', 1);

				// This will default to the 1.5 and 1.6-1.7 behavior.
				$row->paginationrelative = $this->params->get('relative', 0);
			}

		}

	}

	/**
	 * Translate an order code to a field for primary ordering.
	 *
	 * @param   string  $orderDate  The ordering code.
	 *
	 * @return  string  The SQL field(s) to order by.
	 *
	 * @since   3.3
	 */
	private static function getQueryDate($orderDate)
	{

		$db = Factory::getDbo();

		switch ($orderDate)
		{
			// Use created if modified is not set
			case 'modified' :
				$queryDate = ' CASE WHEN a.modified = ' . $db->quote($db->getNullDate()) . ' THEN a.created ELSE a.modified END';
				break;

			// Use created if publish_up is not set
			case 'published' :
				$queryDate = ' CASE WHEN a.publish_up = ' . $db->quote($db->getNullDate()) . ' THEN a.created ELSE a.publish_up END ';
				break;

			// Use created as default
			case 'created' :
			default :
				$queryDate = ' a.created ';
				break;
		}

		return $queryDate;

	}

	private function parent_link ($catid, $joomla_3)
	{

		return $this->params->get('parent_type', 1) ? JURI::root() : ($joomla_3 ? JRoute::_(ContentHelperRoute::getCategoryRoute($catid)) : RouteHelper::getCategoryRoute($catid));

	}

	private function parent_label ($category_title)
	{

		return ($this->params->get('parent_type', 1) ? Text::_('JERROR_LAYOUT_HOME_PAGE') : ($this->params->get('display', 1) ? $category_title : Text::_('JCATEGORY')));

	}

	private function truncate_title ($title)
	{

		$length = $this->params->get('title_characters', 30);
		if ($this->params->get('truncate_long_titles', 0) && (mb_strlen($title) > $length))
			$title = mb_substr($title, 0, $length) . ' ...';
		return $title;

	}

}
