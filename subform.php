<?php
/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.1.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class plgRadicalMart_FieldsSubform extends CMSPlugin
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
	 * @var  JDatabaseDriver
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
	 * Method to add field type to admin list.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $item     List item object.
	 *
	 * @return string|false Field type constant on success, False on failure.
	 *
	 * @since  1.1.0
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
	public function onRadicalMartGetFieldForm($context = null, $form = null, $tmpData = null)
	{
		if ($context !== 'com_radicalmart.field') return;
		if ($tmpData->get('plugin') !== 'subform') return;

		Form::addFormPath(__DIR__ . '/config');
		$form->loadFile('admin');

		$form->setFieldAttribute('display_filter', 'readonly', 'true', 'params');
		$form->setFieldAttribute('display_variability', 'readonly', 'true', 'params');
	}

	/**
	 * Method to set field values.
	 *
	 * @param   string    $context  Context selector string.
	 * @param   Form      $form     Form object.
	 * @param   Registry  $tmpData  Temporary form data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartAfterGetFieldForm($context = null, $form = null, $tmpData = null)
	{
		$form->setValue('display_filter', 'params', '0');
		$form->setValue('display_variability', 'params', '0');
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
	public function onRadicalMartGetProductFieldXml($context = null, $field = null, $tmpData = null)
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'subform') return false;

		$wa = $this->app->getDocument()->getWebAssetManager();
		$wa->addInlineScript('
	        document.addEventListener("DOMContentLoaded", function(event) {
	            let subformContainer = document.querySelector(\'input[name="jform[fields][' . $field->alias . ']"]\').parentElement.parentElement;
	            let subformLabel     = subformContainer.querySelector(\'.control-label\');
	            subformLabel.classList.add(\'fw-bold\', \'mb-2\', \'d-block\', \'w-100\');
	            subformLabel.classList.remove(\'control-label\');
	            subformContainer.querySelector(\'.controls\').classList.remove(\'controls\');
	        });
	    ');

		$fieldNode = new SimpleXMLElement('<field />');
		$fieldNode->addAttribute('name', $field->alias);
		$fieldNode->addAttribute('label', $field->title);
		$fieldNode->addAttribute('type', 'subform');
		$fieldNode->addAttribute('multiple', 'true');
		$fieldNode->addAttribute('layout', 'joomla.form.field.subform.repeatable');

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
			$child->addAttribute('label', $formField->name);

			if (isset($formField->filter))
			{
				$child->addAttribute('filter', $formField->filter);
			}
		}

		$fieldNode->addAttribute('formsource', $fieldsXml->asXML());

		return $fieldNode;
	}


	/**
	 * @param   SimpleXMLElement  $to
	 * @param   SimpleXMLElement  $from
	 *
	 *
	 * @since 1.0.0
	 */
	public function simpleXMLAppend(SimpleXMLElement $to, SimpleXMLElement $from)
	{
		$toDom   = dom_import_simplexml($to);
		$fromDom = dom_import_simplexml($from);
		$toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
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
	public function onRadicalMartGetProductsFieldValue($context = null, $field = null, $value = null)
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
	public function onRadicalMartGetProductFieldValue($context = null, $field = null, $value = null)
	{
		if ($context !== 'com_radicalmart.product') return false;
		if ($field->plugin !== 'subform') return false;
		if (!(int) $field->params->get('display_product', 1)) return false;

		return $this->getFieldValue($field, $value);
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
	protected function getFieldValue($field = null, $value = null, $layout = 'list')
	{
		if (empty($field)) return false;
		if (empty($value)) return false;

		if (!is_array($value)) $value = array($value);

		// Get html
		$layout = $field->params->get('display_product_as', 'default');
		$path   = PluginHelper::getLayoutPath('radicalmart_fields', 'subform', $layout);

		// Render the layout
		ob_start();
		include $path;
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Method to get clean file path.
	 *
	 * @param   object        $field  Field data object.
	 * @param   string|array  $value  Field value.
	 *
	 * @return  string|false  Field string values on success, False on failure.
	 *
	 * @since  1.0.0
	 */
	public static function getCleanFieldValue($value)
	{
		if ($pos = strpos($value, '#'))
		{
			return substr($value, 0, $pos);
		}

		return $value;
	}
}
