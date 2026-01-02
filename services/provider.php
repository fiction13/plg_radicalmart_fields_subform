<?php \defined('_JEXEC') or die;

/*
 * @package   plg_radicalmart_fields_subform
 * @version   __DEPLOY_VERSION__
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\RadicalMartFields\Subform\Extension\Subform;

return new class implements ServiceProviderInterface {

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @since   1.2.0
     */
    public function register(Container $container)
    {
        $container->set(PluginInterface::class,
            function (Container $container) {
                $plugin  = PluginHelper::getPlugin('radicalmart_fields', 'subform');
                $subject = $container->get(DispatcherInterface::class);

                $plugin = new Subform($subject, (array) $plugin);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }

};
