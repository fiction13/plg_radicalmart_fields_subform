<?php
/*
 * @package   plg_radicalmart_fields_subform
 * @version   1.2.0
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
 * @var  object $field  Field data object.
 * @var  array  $values Array of values
 * @var  array  $labels Array of labels
 *
 */

?>

<?php if (!empty($values)) : ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
		<?php foreach ($values as $value) : ?>
			<?php if ($value) : ?>
				<div class="col">
					<?php foreach ($value as $key => $val) : ?>
						<div class="card">
							<div class="card-body">
								<?php echo $labels[$key] . ': ' ?? ''?><?php echo $val; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
    </div>
<?php endif; ?>