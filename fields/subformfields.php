<?php
/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.0.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;

FormHelper::loadFieldClass('list');

class JFormFieldSubformFields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'subformfields';

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
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->key     = (!empty($this->element['key'])) ? (string) $this->element['key']
				: $this->key;
			$this->trigger = (!empty($this->element['trigger'])) ? (string) $this->element['trigger']
				: $this->trigger;
		}

		return $return;
	}

	/**
	 * Method to get the field options.
	 *
	 * @throws  Exception
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		if ($this->_options === null)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('f.id', 'f.alias', 'f.title', 'f.plugin', 'f.params', 'f.plugins'))
				->from($db->quoteName('#__radicalmart_fields', 'f'))
				->order($db->escape('f.ordering') . ' ' . $db->escape('asc'));
			$items = $db->setQuery($query)->loadObjectList('id');

			// Check admin type view
			$app       = Factory::getApplication();
			$component = $app->input->get('option', 'com_radicalmart');
			$view      = $app->input->get('view', 'field');
			$id        = $app->input->getInt('id', 0);
			$sameView  = ($app->isClient('administrator') && $component == 'com_radicalmart' && $view == 'field');
			$key       = $this->key;

			// Prepare options
			$options = parent::getOptions();
			foreach ($items as $i => $item)
			{
				$option          = new stdClass();
				$option->value   = $item->$key;
				$option->text    = $item->title;
				$option->disable = ($sameView && $item->id == $id);

				if ($this->trigger)
				{
					if (empty($item->plugin)) continue;
					$item->params  = new Registry($item->params);
					$item->plugins = new Registry($item->plugins);

					$result = RadicalMartHelperPlugins::triggerPlugin('radicalmart_fields', $item->plugin, $this->trigger,
						array(&$option, $item));
					if (!$result) continue;
				}

				if ($item->plugin == 'subform') continue;

				$options[] = $option;
			}

			$this->_options = $options;
		}

		return $this->_options;
	}
}