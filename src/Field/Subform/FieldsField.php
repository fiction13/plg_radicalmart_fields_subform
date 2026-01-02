<?php namespace Joomla\Plugin\RadicalMartFields\Subform\Field\Subform;

/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.2.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Component\RadicalMart\Administrator\Helper\PluginsHelper;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseInterface;
use SimpleXMLElement;
use stdClass;

class FieldsField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'subform_fields';

	/**
	 * The value key.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $key = 'id';

	/**
	 * The value key.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $trigger = null;

	/**
	 * Field options array.
	 *
	 * @var  array
	 *
	 * @since  1.0.0
	 */
	protected $_options = null;

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.0.0
	 */
	public function setup(SimpleXMLElement $element, mixed $value, ?string $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->key     = !empty($this->element['key']) ? (string) $this->element['key'] : $this->key;
			$this->trigger = !empty($this->element['trigger']) ? (string) $this->element['trigger'] : $this->trigger;
		}

		return $return;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @throws  Exception|\Exception
	 *
	 * @since  1.0.0
	 */
	protected function getOptions(): array
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select(['id', 'alias', 'title', 'plugin', 'params', 'plugins'])
			->from($db->quoteName('#__radicalmart_fields'))
			->order($db->quoteName('ordering') . ' ASC');
		$items = $db->setQuery($query)->loadObjectList('id');

		$app       = Factory::getApplication();
		$component = $app->input->get('option', 'com_radicalmart');
		$view      = $app->input->get('view', 'field');
		$id        = $app->input->getInt('id', 0);
		$sameView  = ($app->isClient('administrator') && $component === 'com_radicalmart' && $view === 'field');

		$options = parent::getOptions();
		foreach ($items as $item)
		{
			if ($item->plugin === 'subform') continue;

			$option          = new stdClass();
			$option->value   = $item->{$this->key};
			$option->text    = $item->title;
			$option->disable = ($sameView && $item->id === $id);

			if ($this->trigger && !empty($item->plugin))
			{
				$item->params  = new Registry($item->params);
				$item->plugins = new Registry($item->plugins);
				if (!PluginsHelper::triggerPlugin('radicalmart_fields', $item->plugin, $this->trigger, [&$option, $item]))
				{
					continue;
				}
			}

			$options[] = $option;
		}

		return $options;
	}

}