<?php namespace Joomla\Plugin\RadicalMartFields\Subform\Extension;

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
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use SimpleXMLElement;

class Subform extends CMSPlugin
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
     * Method to add field config.
     *
     * @param string $context Context selector string.
     * @param Form $form Form object.
     * @param Registry $tmpData Temporary form data.
     *
     * @since  1.0.0
     */
    public function onRadicalMartGetFieldForm(string $context, Form $form, Registry $tmpData): void
    {
	    if ($context !== 'com_radicalmart.field' || $tmpData->get('plugin') !== 'subform') {
		    return;
	    }

	    $form->addFormPath(JPATH_PLUGINS . '/radicalmart_fields/subform/forms');
	    $form->loadFile('subform');

        // Remove from filter
	    $form->setFieldAttribute('display_filter', 'type', 'hidden', 'params');
	    $form->setValue('display_filter', 'params', 0);

        // Remove from variability
	    $form->setFieldAttribute('display_variability', 'type', 'hidden', 'params');
	    $form->setValue('display_variability', 'params', 0);
    }

    /**
     * Method to add field to product form.
     *
     * @param string $context Context selector string.
     * @param object $field Field data object.
     * @param Registry $tmpData Temporary form data.
     *
     * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
     *
     * @since  1.0.0
     */
	public function onRadicalMartGetProductFieldXml(string $context, object $field, Registry $tmpData): false|SimpleXMLElement
	{
		if ($context !== 'com_radicalmart.product' || $field->plugin !== 'subform') {
			return false;
		}

		Factory::getApplication()->getDocument()->addScriptDeclaration(
			"document.addEventListener('DOMContentLoaded', function() {
                let subformContainer = document.querySelector('input[name=\"jform[fields][" . $field->alias . "]\"]')?.closest('.form-group');
                if (subformContainer) {
                    let subformLabel = subformContainer.querySelector('label');
                    if (subformLabel) {
                        subformLabel.classList.add('fw-bold', 'mb-2');
                    }
                }
            });"
		);

		$fieldNode = new SimpleXMLElement('<field />');
		$fieldNode->addAttribute('name', $field->alias);
		$fieldNode->addAttribute('label', $field->title);
		$fieldNode->addAttribute('type', 'subform');
		$fieldNode->addAttribute('multiple', 'true');
//		$fieldNode->addAttribute('full_width', 'true');

		$layout_field = match ($field->params->get('layout', 'list')) {
			'list' => 'joomla.form.field.subform.repeatable',
			'table' => 'joomla.form.field.subform.repeatable-table',
			default => 'joomla.form.field.subform.repeatable',
		};

		$fieldNode->addAttribute('layout', $layout_field);

		$fieldsXml = new SimpleXMLElement('<form/>' );
		$fields = $fieldsXml->addChild('fields');

		$formFields = json_decode(json_encode($field->params->get('fields')), true) ?? [];

		foreach ($formFields as $formField) {
			$child = $fields->addChild('field');
			$child->addAttribute('name', (string) $formField['name']);
			$child->addAttribute('type', (string) $formField['type']);
			$child->addAttribute('label', (string) $formField['label']);
			if (!empty($formField['filter'])) {
				$child->addAttribute('filter', (string) $formField['filter']);
			}
		}

		$fieldNode->addAttribute('formsource', $fieldsXml->asXML());
		return $fieldNode;
	}

    /**
     * @param SimpleXMLElement $to
     * @param SimpleXMLElement $from
     *
     *
     * @since 1.0.0
     */
    public function simpleXMLAppend(SimpleXMLElement $to, SimpleXMLElement $from)
    {
        $toDom = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    /**
     * Method to add field to filter form.
     *
     * @param string $context Context selector string.
     * @param object $field Field data object.
     * @param array $data Data.
     *
     * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
     *
     * @since  1.0.0
     */
    public function onRadicalMartGetFilterFieldXml($context = null, $field = null, $data = null)
    {
        if ($field->plugin === 'subform') {
            return false;
        }
    }

    /**
     * Method to add field to meta variability select.
     *
     * @param string $context Context selector string.
     * @param object $field Field data object.
     * @param object $meta Meta product data object.
     * @param object $product Current product data object.
     *
     * @return false|SimpleXMLElement SimpleXMLElement on success, False on failure.
     *
     * @since 1.1.0
     */
    public function onRadicalMartGetMetaVariabilityProductFieldXml($context = null, $field = null, $meta = null, $product = null)
    {
        if ($field->plugin === 'subform') {
            return false;
        }
    }

    /**
     * Method to modify query.
     *
     * @param string $context Context selector string.
     * @param DatabaseQuery $query DatabaseQuery  A DatabaseQuery object to retrieve the data set
     * @param object $field Field data object.
     * @param array|string $value Value.
     *
     * @since  1.0.0
     */
    public function onRadicalMartGetProductsListQuery($context = null, $query = null, $field = null, $value = null)
    {
        if ($field->plugin === 'subform') {
            return;
        }
    }

    /**
     * Method to add field value to products list.
     *
     * @param string $context Context selector string.
     * @param object $field Field data object.
     * @param array|string $value Field value.
     *
     * @return  string  Field html value.
     *
     * @since  1.0.0
     */
    public function onRadicalMartGetProductsFieldValue($context = null, $field = null, $value = null)
    {
        if ($context !== 'com_radicalmart.category' && $context !== 'com_radicalmart.products') {
            return false;
        }

        if ($field->plugin !== 'subform') {
            return false;
        }

        if (!(int)$field->params->get('display_products', 1)) {
            return false;
        }

        return $this->getFieldValue($field, $value, $field->params->get('display_products_as', 'list'));
    }

    /**
     * Method to add field value to products list.
     *
     * @param string $context Context selector string.
     * @param object $field Field data object.
     * @param array|string $value Field value.
     *
     * @return  string  Field html value.
     *
     * @since  1.0.0
     */
    public function onRadicalMartGetProductFieldValue($context = null, $field = null, $value = null)
    {
        if ($context !== 'com_radicalmart.product') {
            return false;
        }

        if ($field->plugin !== 'subform') {
            return false;
        }

        if (!(int)$field->params->get('display_product', 1)) {
            return false;
        }

        return $this->getFieldValue($field, $value, $field->params->get('display_product_as', 'list'));
    }

    /**
     * Method to add field value to products list.
     *
     * @param object $field Field data object.
     * @param string|array $value Field value.
     * @param string $layout Layout name.
     *
     * @return  string|false  Field string values on success, False on failure.
     *
     * @since  1.0.0
     */
	protected function getFieldValue(object $field, array|string $value, string $layout = 'list'): string|false
	{
		if (empty($field) || empty($value)) {
			return false;
		}
		$value = (array) $value;
		return LayoutHelper::render('plugins.radicalmart_fields.subform.display.' . $layout, ['field' => $field, 'values' => $value]);
	}

    /**
     * Method to get clean file path.
     *
     * @param object $field Field data object.
     * @param string|array $value Field value.
     *
     * @return  string|false  Field string values on success, False on failure.
     *
     * @since  1.0.0
     */
    public static function getCleanFieldValue($value)
    {
        if ($pos = strpos($value, '#')) {
            return substr($value, 0, $pos);
        }

        return $value;
    }

}
