<?php namespace Joomla\Plugin\RadicalMartFields\Subform\Extension;

/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.2.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Event\SubscriberInterface;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use SimpleXMLElement;

class Subform extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  1.0.0
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  DatabaseDriver
	 *
	 * @since  1.0.0
	 */
	protected $db = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;


	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartGetFieldType'          => 'onRadicalMartGetFieldType',
			'onRadicalMartGetFieldForm'          => 'onRadicalMartGetFieldForm',
			'onRadicalMartGetProductFieldXml'    => 'onRadicalMartGetProductFieldXml',
			'onRadicalMartGetProductsFieldValue' => 'onRadicalMartGetProductsFieldValue',
			'onRadicalMartGetProductFieldValue'  => 'onRadicalMartGetProductFieldValue',
			'onRadicalMartAfterGetFieldForm'     => 'onRadicalMartAfterGetFieldForm',
			'onContentNormaliseRequestData'      => 'onContentNormaliseRequestData',
			'onContentPrepareForm'               => 'onContentPrepareForm'
		];
	}

	/**
	 * Listener for the `onContentNormaliseRequestData` event.
	 *
	 * @param   mixed  $event
	 *
	 * @throws  \Exception
	 *
	 * @since  1.2.0
	 */
	public function onContentNormaliseRequestData(mixed $event): void
	{
		$context = $event->getArgument(0);
		$item    = $event->getArgument(1);

		if ($context === 'com_radicalmart.field')
		{
			$params = new Registry($item->params);
			$params = $this->normaliseParams($params);

			// Set params
			$item->params = $params->toArray();

			$event->setArgument(1, $item);
		}
	}

	/**
	 * Add form override.
	 *
	 * @param   mixed  $event
	 *
	 * @throws  \Exception
	 *
	 * @since  1.2.0
	 */
	public function onContentPrepareForm(mixed $event): void
	{
		/** @var Form $form */
		$form = $event->getArgument(0);
		$data = $event->getArgument(1);

		$formName = $form->getName();

		if (!Factory::getApplication()->isClient('administrator')) return;

		// Product
		if ($formName === 'com_radicalmart.field')
		{
			if (!empty($data) && (!empty($data->id) || !empty($data['id'])))
			{
				$fieldXml = $form->getFieldXml('fields', 'params');
				foreach ($fieldXml->form->field as $field)
				{
					if ((string) $field['name'] === 'name')
					{
						$field['readonly'] = 'true';
						break;
					}
				}

				$form->setField($fieldXml, 'params', false, 'content');
			}
		}
	}

	/**
	 * Method to add field type to admin list.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $item     List item object.
	 *
	 * @return string|false Field type constant on success, False on failure.
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartGetFieldType($context = null, $item = null)
	{
		return 'PLG_RADICALMART_FIELDS_SUBFORM_FIELD_TYPE';
	}

	/**
	 * Method to add field config.
	 *
	 * @param   string    $context  Context selector string.
	 * @param   Form      $form     Form object.
	 * @param   Registry  $tmpData  Temporary form data.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFieldForm(string $context, Form $form, Registry $tmpData): void
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'subform')
		{
			return;
		}

		$form->addFormPath(JPATH_PLUGINS . '/radicalmart_fields/subform/forms');
		$form->loadFile('subform');

		$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
		$form->removeField('display_variability_as', 'params');

		$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
		$form->removeField('display_filter_as', 'params');
	}

	/**
	 * Method to add field to product form.
	 *
	 * @param   string    $context  Context selector string.
	 * @param   object    $field    Field data object.
	 * @param   Registry  $tmpData  Temporary form data.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldXml(string $context, object $field, Registry $tmpData): false|SimpleXMLElement
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'subform') return false;

		// Check layout
		$layout     = $field->params->get('layout', 'list');
		$layoutName = '';

		if ($layout === 'list') $layoutName = 'joomla.form.field.subform.repeatable';
		if ($layout === 'table') $layoutName = 'joomla.form.field.subform.repeatable-table';

		$fieldNode = new SimpleXMLElement('<field />');
		$fieldNode->addAttribute('name', $field->alias);
		$fieldNode->addAttribute('label', $field->title);
		$fieldNode->addAttribute('type', 'subform');
		$fieldNode->addAttribute('multiple', 'true');
		$fieldNode->addAttribute('parentclass', 'stack');
		$fieldNode->addAttribute('layout', $layoutName);

		// Build the form source
		$fieldsXml = new SimpleXMLElement('<form/>');
		$fields    = $fieldsXml->addChild('fields');

		// Get the form settings
		$formFields = $field->params->get('fields');

		// Add the fields to the form
		foreach ($formFields as $index => $formField)
		{
			$child = $fields->addChild('field');
			$child->addAttribute('name', $formField->name);
			$child->addAttribute('type', $formField->type);
			$child->addAttribute('label', !empty($formField->label) ? $formField->label : $formField->name);

			if (isset($formField->fieldfilter))
			{
				$child->addAttribute('filter', $formField->fieldfilter);
			}
		}

		$fieldNode->addAttribute('formsource', $fieldsXml->asXML());

		return $fieldNode;
	}

	/**
	 * Method to change field form.
	 *
	 * @param   string|null    $context  Context selector string.
	 * @param   Form|null      $form     Form object.
	 * @param   Registry|null  $tmpData  Temporary form data.
	 *
	 * @since  1.2.0
	 */
	public function onRadicalMartAfterGetFieldForm(string $context = null, Form $form = null, Registry $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'subform')
		{
			return;
		}

		$form->setValue('display_filter', 'params', '0');
		$form->setValue('display_variability', 'params', '0');
	}

	/**
	 * Method to add field to filter form.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $field    Field data object.
	 * @param   array   $data     Data.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetFilterFieldXml($context = null, $field = null, $data = null): false|SimpleXMLElement
	{
		if ($field->plugin === 'subform') return false;
	}

	/**
	 * Method to add field to meta variability select.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $field    Field data object.
	 * @param   object  $meta     Meta product data object.
	 * @param   object  $product  Current product data object.
	 *
	 * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
	 *
	 * @since 1.1.0
	 */
	public function onRadicalMartGetMetaVariabilityProductFieldXml($context = null, $field = null, $meta = null, $product = null): false|SimpleXMLElement
	{
		if ($field->plugin === 'subform') return false;
	}

	/**
	 * Method to modify query.
	 *
	 * @param   string         $context  Context selector string.
	 * @param   DatabaseQuery  $query    DatabaseQuery  A DatabaseQuery object to retrieve the data set
	 * @param   object         $field    Field data object.
	 * @param   array|string   $value    Value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsListQuery($context = null, $query = null, $field = null, $value = null): false|SimpleXMLElement
	{
		if ($field->plugin === 'subform') return false;
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string        $context  Context selector string.
	 * @param   object        $field    Field data object.
	 * @param   array|string  $value    Field value.
	 *
	 * @return  string  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsFieldValue($context = null, $field = null, $value = null): string
	{
		if ($context !== 'com_radicalmart.category' && $context !== 'com_radicalmart.products') return false;
		if ($field->plugin !== 'subform') return false;
		if (!(int) $field->params->get('display_products', 1)) return false;

		return $this->getFieldValue($field, $value, $field->params->get('display_products_as', 'list'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   string        $context  Context selector string.
	 * @param   object        $field    Field data object.
	 * @param   array|string  $value    Field value.
	 *
	 * @return  string  Field html value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductFieldValue($context = null, $field = null, $value = null): string
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'subform') return false;
		if (!(int) $field->params->get('display_product', 1)) return false;

		return $this->getFieldValue($field, $value, $field->params->get('display_product_as', 'list'));
	}

	/**
	 * Method to add field value to products list.
	 *
	 * @param   object        $field   Field data object.
	 * @param   string|array  $value   Field value.
	 * @param   string        $layout  Layout name.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	protected function getFieldValue(object $field, array|string $value, string $layout = 'list'): string|false
	{
		if (empty($field) || empty($value)) return false;

		$values  = (array) $value;
		$params = $field->params->toArray();
		$labels = ArrayHelper::getColumn($params['fields'], 'label', 'name');

		return LayoutHelper::render('plugins.radicalmart_fields.subform.display.' . $layout, ['field' => $field, 'values' => $values, 'labels' => $labels]);
	}

	/**
	 * @param   Registry  $params
	 *
	 * @return Registry
	 *
	 * @since 1.2.0
	 */
	public function normaliseParams($params)
	{
		$fields = ArrayHelper::fromObject($params->get('fields', new \stdClass()));

		if (!empty($fields))
		{
			foreach ($fields as &$field)
			{
				// Create alias
				if (empty($field['name']))
				{
					$field['name'] = OutputFilter::stringURLSafe($field['label'], 'ru-RU');
				}
			}

			$params->set('fields', $fields);
		}

		return $params;
	}
}
