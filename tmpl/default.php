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

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  object $field Field data object.
 * @var  array $values
 *
 */

?>

<?php if (!empty($values)) : ?>
	<ul class="list-unstyled mb-0">
		<?php foreach ($values as $value) : ?>

			<?php if ($value) : ?>
				<li>
					<?php foreach ($value as $val) : ?>
						<div>
							<?php echo $val; ?>
						</div>
					<?php endforeach; ?>
				</li>
			<?php endif; ?>

		<?php endforeach; ?>
	</ul>
<?php endif; ?>
