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

?>

<?php if (!empty($value)) : ?>
    <ul class="uk-list uk-margin-remove">
        <?php foreach ($value as $vals) : ?>

            <?php if ($vals) : ?>
                <li>
                    <?php foreach ($vals as $val) : ?>
                        <div>
                            <?php echo $val; ?>
                        </div>
                    <?php endforeach; ?>
                </li>
            <?php endif; ?>

        <?php endforeach; ?>
    </ul>
<?php endif; ?>