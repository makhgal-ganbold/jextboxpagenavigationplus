<?php

/**
 * @package    "JExtBOX Page Navigation Plus" plugin for Joomla!
 * @reference  plg_content_pagenavigation - the core plugin of Joomla CMS
 * @copyright  Copyright (c) 2026 Makhgal Ganbold
 * @author     Makhgal Ganbold
 * @link       https://www.jextbox.com
 * @license    GNU/GPL License - https://www.gnu.org/licenses/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Content\JExtBOXPageNavigationPlus\Extension\JExtBOXPageNavigationPlus;

return new class () implements ServiceProviderInterface {

	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin = new JExtBOXPageNavigationPlus(
					(array) PluginHelper::getPlugin('content', 'jextboxpagenavigationplus')
				);
				$plugin->setApplication(Factory::getApplication());
				$plugin->setDatabase($container->get(DatabaseInterface::class));
				return $plugin;
			}
		);
	}
};
