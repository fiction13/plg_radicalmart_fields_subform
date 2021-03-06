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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Version;
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

		// $formName = ((new Version())->isCompatible('4.0')) ? 'admin_j4' : 'admin_j3';

		Form::addFormPath(__DIR__ . '/config');
		$form->loadFile('admin_j3');

		// Remove from filter
		$form->setFieldAttribute('display_filter', 'type', 'hidden', 'params');
		$form->setValue('display_filter', 'params', 0);

		// Remove from variability
		$form->setFieldAttribute('display_variability', 'type', 'hidden', 'params');
		$form->setValue('display_variability', 'params', 0);
	}

	/**
	 * Prepare options data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $objData  Input data.
	 * @param   Form    $form     Joomla Form object.
	 *
	 * @throws  Exception
	 *
	 * @since  1.0.0
	 */
	public function onContentNormaliseRequestData($context, $objData, $form)
	{
		if ($context === 'com_radicalmart.field')
		{
			// noop
		}
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

		// Check Joomla 4
		if ((new Version())->isCompatible('4.0'))
		{
			Factory::getDocument()->addScriptDeclaration('
	            document.addEventListener("DOMContentLoaded", function(event) {
	                let subformContainer = document.querySelector(\'input[name="jform[fields][' . $field->alias . ']"]\').parentElement.parentElement;
	                let subformLabel     = subformContainer.querySelector(\'.control-label\');
	                
	                subformLabel.classList.add(\'fw-bold\');
	                subformLabel.classList.add(\'mb-2\');
	                subformLabel.classList.remove(\'control-label\');
	                subformContainer.querySelector(\'.controls\').classList.remove(\'controls\');
	            });
	        ');
		}
		else
		{
			Factory::getDocument()->addScriptDeclaration('
	            document.addEventListener("DOMContentLoaded", function(event) {
	                let subformContainer = document.querySelector(\'input[name="jform[fields][' . $field->alias . ']"]\').parentElement.parentElement;
	                let subformLabel     = subformContainer.querySelector(\'.control-label\');
	                
	                console.info(subformContainer);
	                
	                subformLabel.classList.add(\'lead\');
	                subformLabel.classList.remove(\'control-label\');
	                subformContainer.querySelector(\'.controls\').classList.remove(\'controls\');
	            });
	        ');
		}

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
			// For subform J4
			// JLoader::register('RadicalMartHelperPlugins', JPATH_ADMINISTRATOR . '/com_radicalmart/helpers/plugins.php');
			// JLoader::register('RadicalMartHelperFields', JPATH_ADMINISTRATOR . '/com_radicalmart/helpers/fields.php');
			// $field = RadicalMartHelperFields::getFields($formField->customfield);

			// if (!$field) continue;
			// $field = array_shift($field);
			// $parentNode = RadicalMartHelperPlugins::triggerPlugin('radicalmart_fields', $field->plugin, 'onRadicalMartGetProductFieldXml', array($context, $field, null));
			// $this->simpleXMLAppend($fieldsXml, $parentNode);

			// Old subform version for J3
			$child = $fields->addChild('field');
			$child->addAttribute('name', $formField->name);
			$child->addAttribute('type', $formField->type);
			$child->addAttribute('label', $formField->name);

			if (isset($formField->fieldfilter))
			{
				$child->addAttribute('filter', $formField->fieldfilter);
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
	public function simpleXMLAppend(SimpleXMLElement $to, SimpleXMLElement $from) {
	    $toDom   = dom_import_simplexml($to);
	    $fromDom = dom_import_simplexml($from);
	    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
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
	public function onRadicalMartGetFilterFieldXml($context = null, $field = null, $data = null)
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
	public function onRadicalMartGetMetaVariabilityProductFieldXml($context = null, $field = null, $meta = null, $product = null)
	{
		if ($field->plugin === 'subform') return false;
	}

	/**
	 * Method to modify query.
	 *
	 * @param   string          $context  Context selector string.
	 * @param   JDatabaseQuery  $query    JDatabaseQuery  A JDatabaseQuery object to retrieve the data set
	 * @param   object          $field    Field data object.
	 * @param   array|string    $value    Value.
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetProductsListQuery($context = null, $query = null, $field = null, $value = null)
	{
		if ($field->plugin === 'subform') return;
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
	protected function getFieldValue($field = null, $value = null, $layout = 'list')
	{
		if (empty($field)) return false;
		if (empty($value)) return false;

		if (!is_array($value)) $value = array($value);

		$html = LayoutHelper::render('plugins.radicalmart_fields.subform.display.' . $layout, array(
			'field' => $field, 'values' => $value));

		return $html;
	}

	/**
	 * Method to get clean file path.
	 *
	 * @param   object        $field   Field data object.
	 * @param   string|array  $value   Field value.
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
