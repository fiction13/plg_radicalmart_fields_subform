<?php namespace Joomla\Plugin\RadicalMartFields\Subform\Field;

/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.0.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2022 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class SubformlayoutField extends FormField
{
    /**
     * The form field type.
     *
     * @var  string
     *
     * @since  1.4.0
     */
    protected $type = 'subformlayout';

    /**
	 * Method to get the field input for plugin layouts.
	 *
	 * @return  string  The field input.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		// Get the client id.
		$clientId = $this->form->getValue('client_id');
		$clientId = (int) $clientId;
		$client   = ApplicationHelper::getClientInfo($clientId);

		// Get the plugin.
		$plugin = $this->form->getValue('plugin');
		$plugin = preg_replace('#\W#', '', $plugin);

		// Get the template.
		$template = (string) $this->element['template'];
		$template = preg_replace('#\W#', '', $template);

		// Get sublayout
		$sublayout = $this->element['sublayout'] ? (string) $this->element['sublayout'] : 'display';

		// Get the style.
		$template_style_id = 0;

		if ($this->form instanceof Form)
		{
			$template_style_id = $this->form->getValue('template_style_id', null, 0);
			$template_style_id = (int) preg_replace('#\W#', '', $template_style_id);
		}

		// If an extension and view are present build the options.
		if ($plugin && $client)
		{
			// Get the database object and a new query object.
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);

			// Build the query.
			$query->select(
				[
					$db->quoteName('element'),
					$db->quoteName('name'),
				]
			)
				->from($db->quoteName('#__extensions', 'e'))
				->where(
					[
						$db->quoteName('e.client_id') . ' = ' . $clientId,
						$db->quoteName('e.type') . ' = ' . $db->quote('template'),
						$db->quoteName('e.enabled') . ' = 1',
					]
				);

			if ($template)
			{
				$query->where($db->quoteName('e.element') . ' = :template')
					->bind(':template', $template);
			}

			if ($template_style_id)
			{
				$query->join('LEFT', $db->quoteName('#__template_styles', 's'), $db->quoteName('s.template') . ' = ' . $db->quoteName('e.element'))
					->where($db->quoteName('s.id') . ' = '. (int) $template_style_id);
			}

			// Set the query and load the templates.
			$db->setQuery($query);
			$templates = $db->loadObjectList('element');

			// Build the search paths for plugin layouts.
			$plugin_path = Path::clean($client->path . '/layouts/plugins/radicalmart_fields/' . $plugin . '/' . $sublayout);

			// Prepare array of component layouts
			$plugin_layouts = array();

			// Prepare the grouped list
			$groups = array();

			// Add the layout options from the plugin path.
			if (is_dir($plugin_path) && ($plugin_layouts = Folder::files($plugin_path, '^[^_]*\.php$')))
			{
				// Create the group for the plugin
				$groups['_'] = array();
				$groups['_']['id'] = $this->id . '__';
				$groups['_']['text'] = Text::sprintf('PLG_RADICALMART_FIELDS_SUBFORM_LAYOUT_OPTION');
				$groups['_']['items'] = array();

				foreach ($plugin_layouts as $file)
				{
					// Add an option to the plugin group
					$value = basename($file, '.php');
					$groups['_']['items'][] = HTMLHelper::_('select.option', $value, ucfirst($value));
				}
			}

			// Loop on all templates
			if ($templates)
			{
				foreach ($templates as $template)
				{

					$template_path = Path::clean($client->path . '/templates/' . $template->element . '/html/layouts/plugins/radicalmart_fields/' . $plugin . '/' . $sublayout);

					// Add the layout options from the template path.
					if (is_dir($template_path) && ($files = Folder::files($template_path, '^[^_]*\.php$')))
					{
						foreach ($files as $i => $file)
						{
							// Remove layout that already exist in component ones
							if (\in_array($file, $plugin_layouts))
							{
								unset($files[$i]);
							}
						}

						if (\count($files))
						{
							// Create the group for the template
							$groups[$template->element] = array();
							$groups[$template->element]['id'] = $this->id . '_' . $template->element;
							$groups[$template->element]['text'] = Text::sprintf('JOPTION_FROM_TEMPLATE', $template->name);
							$groups[$template->element]['items'] = array();

							foreach ($files as $file)
							{
								// Add an option to the template group
								$value = basename($file, '.php');
								$groups[$template->element]['items'][] = HTMLHelper::_('select.option', $template->element . ':' . $value, $value);
							}
						}
					}
				}
			}

			// Compute attributes for the grouped list
			$attr = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
			$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';

			// Prepare HTML code
			$html = array();

			// Compute the current selected values
			$selected = array($this->value);

			// Add a grouped list
			$html[] = HTMLHelper::_(
				'select.groupedlist', $groups, $this->name,
				array('id' => $this->id, 'class' => 'form-select', 'group.id' => 'id', 'list.attr' => $attr, 'list.select' => $selected)
			);

			return implode($html);
		}
		else
		{
			return '';
		}
	}

}