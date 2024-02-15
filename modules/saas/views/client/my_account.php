<?php

defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$currency = get_base_currency();
$is_client = is_client_logged_in();
$showing_subscribed_card = true;

$custom_repeat = $package->metadata->invoice->recurring == 'custom';
$interval = $custom_repeat ? $package->metadata->invoice->repeat_every_custom : $package->metadata->invoice->recurring;
$interval_type = $custom_repeat ? $package->metadata->invoice->repeat_type_custom . 's' : 'months';
$billing_cycle = strtolower(($interval > 1 ? $interval : '') . _l($interval > 1 ? "perfex_saas" . $interval_type : "perfex_saas" . $interval_type . '_singular'));

$subscribed = !empty($invoice->{perfex_saas_column('packageid')}) && $invoice->{perfex_saas_column('packageid')} == $package->id;

$on_trial = isset($invoice) && $invoice->status == Invoices_model::STATUS_DRAFT;
$days_left = $on_trial ? (int)\Carbon\Carbon::parse($invoice->duedate)->diffInDays() : '';
$invoice_days_left = $invoice ? \Carbon\Carbon::parse($invoice->duedate)->diffInDays() : '';

$limitations = $package->metadata->limitations;
$discounts = $package->metadata->formatted_discounts ?? [];

if (isset($package->metadata->storage_limit->unit)) {
    $storage = (int)($package->metadata->storage_limit->size ?? 0);
    $limitations = array_merge(
        (array)$package->metadata->limitations ?? [],
        ['storage' => $storage === -1 ? _l('perfex_saas_unlimited') : $storage . ' ' . ($package->metadata->storage_limit->unit ?? 'B')],
    );
}

$subscribed = !empty($invoice->{perfex_saas_column('packageid')}) && $invoice->{perfex_saas_column('packageid')} == $package->id;
$unlimited_resources = [];

$modules = $this->perfex_saas_model->modules();
$purchased_modules = $invoice->purchased_modules;

$na = _l('perfex_saas_na');

$allow_module_request = get_option('perfex_saas_enable_custom_module_request') == "1";
$module_request_url = get_option('perfex_saas_custom_module_request_form');
$module_request_url = empty($module_request_url) ? site_url('clients/open_ticket?request_custom_module&title=' . _l('perfex_saas_custom_module_request')) : $module_request_url;

$taxes = $package->metadata->invoice->taxname ?? [];

?>


<div class="panel ps">
    <div class="panel-body">
        <div class="tw-flex tw-flex-col tw-justify-center tw-items-center tw-mb-3">
            <h1 class="tw-mt-0">
                <?php if ($subscribed) : ?>
                    <i class="fa fa-check-circle text-success <?= perfex_saas_is_single_package_mode() ? 'fa-2x' : ''; ?>"></i>
                <?php endif; ?>
                <?php if (!perfex_saas_is_single_package_mode()) echo $package->name; ?>
            </h1>
            <div>
                <span class="tw-bg-neutral-700 tw-text-lg tw-text-white badge badge-primary tw-font-bold">
                    <?= $package->price; ?>
                    <?php if ((float)$package->price < (float)$invoice->subtotal) echo " + " . (float)$invoice->subtotal - (float)$package->price; ?>
                    <?= $currency->name; ?>
                    <small class="text-lowercase">/ <?= $billing_cycle; ?></small>
                </span>
            </div>
            <?php if (!$on_trial) : ?>
                <a class="text-center" href="<?= base_url("invoice/$invoice->id/$invoice->hash"); ?>">
                    <?php require(__DIR__ . '/../packages/next_invoice_date.php'); ?>
                </a>
            <?php endif; ?>
            <?php include(__DIR__ . '/includes/invoice_notification.php'); ?>
        </div>

        <?= form_open('', ['method' => 'POST']); ?>
        <div class="row">
            <div class="col-md-9">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th><?= _l('perfex_saas_feature_or_module'); ?></th>
                                <th><?= _l('perfex_saas_current_limit'); ?></th>
                                <th><?= _l('perfex_saas_new_limit'); ?></th>
                                <th><?= _l('perfex_saas_unit_price'); ?>/<?= $billing_cycle; ?></th>
                                <th><?= _l('perfex_saas_price_addition'); ?>/<?= $billing_cycle; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($limitations as $resources => $limit) :
                                $is_unlimited = (int)$limit === -1;
                                $is_storage = $resources === 'storage';
                                $current_extra_limit = $invoice->custom_limits->{$resources} ?? '';
                                $unit_price = $package->metadata->limitations_unit_price->{$resources} ?? 0;
                                if ($is_unlimited) {
                                    $unlimited_resources[] = _l('perfex_saas_limit_' . $resources);
                                    continue;
                                }

                                if ($is_storage) {
                                    if (!isset($package->metadata->storage_limit->unit_price))
                                        continue;

                                    $unit_price = $package->metadata->storage_limit->unit_price;
                                    if ($unit_price === "") {
                                        continue;
                                    }
                                }

                                $unit_price = (float)$unit_price;
                            ?>
                                <tr>
                                    <td><?= _l('perfex_saas_limit_' . $resources); ?></td>
                                    <td><?= $limit; ?></td>
                                    <td>
                                        <?php if (!$is_unlimited) : ?>

                                            <div class="<?= $is_storage ? 'input-group' : ''; ?>">
                                                <input type="number" min="0" step="1" class="form-control feature-limit tw-p-1" name="custom_limits[<?= $resources; ?>]" value="<?= $current_extra_limit; ?>" data-unit-price="<?= $unit_price; ?>" data-id="<?= $resources; ?>">
                                                <?php if ($is_storage) : ?>
                                                    <span class="input-group-addon tw-px-1 tw-border-l-0"><?= $package->metadata->storage_limit->unit; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-lowercase">
                                        <span class="base-price"><?= app_format_money($unit_price, $currency); ?></span>

                                        <!-- Display discount -->
                                        <?php if (!empty($discounts->{$resources})) : ?>
                                            <div class="tw-mt-1 tw-flex tw-flex-col text-info discount <?= $resources; ?>">
                                                <?php
                                                asort($discounts->{$resources});
                                                foreach ($discounts->{$resources} as $key => $value) : ?>
                                                    <span class="tw-text-xs <?= $resources . $key; ?>">
                                                        <?= $value['unit']; ?>+
                                                        <span class="tw-ml-1"><?= app_format_money($unit_price - (($unit_price * ((float)$value['percent']) / 100)), $currency); ?>
                                                            <sup>(<?= $value['percent']; ?>% off)</sup></span>
                                                    </span>
                                                <?php endforeach ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-lowercase"><span class="price-addition" data-price="0"><?= app_format_money(0, $currency); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach ?>

                            <!-- unlimited  resources -->

                            <?php
                            // Add free modules to unlimited resources list
                            if (!empty($package->modules)) :
                                foreach ($package->modules as $key => $value) :
                                    $unlimited_resources[] = $CI->perfex_saas_model->get_module_custom_name($value);
                                endforeach;
                            endif;
                            ?>
                            <tr>
                                <td class="!tw-max-w-xs">
                                    <?= trim(implode(', ', $unlimited_resources)); ?></td>
                                <td><?= _l('perfex_saas_unlimited'); ?></td>
                                <td><?= $na; ?></td>
                                <td class="text-lowercase"><?= app_format_money("0", $currency); ?></td>
                                <td class="text-lowercase"><?= app_format_money("0", $currency); ?></td>
                            </tr>
                            <!-- Add more rows for other features -->

                            <!-- Add more rows for other free base modules -->
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="tw-flex tw-gap-3 tw-items-center tw-justify-between">
                                        <strong class="tw-mb-0 tw-text-base"><?= _l('perfex_saas_premium_modules'); ?></strong>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#moduleModal">
                                            <?= _l('perfex_saas_select_modules'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr id="paid-modules">
                                <td colspan="5" class="text-center !tw-border-0 !tw-p-0">
                                </td>
                            </tr>

                        </tbody>

                    </table>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="tw-bg-neutral-200 tw-p-1 tw-mb-4">
                        <table class="table table-condensed">
                            <tfoot>
                                <tr>
                                    <th colspan="4"><?= _l('perfex_saas_base_price'); ?></th>
                                    <td><span class="baseprice"><?= app_format_money($package->price, $currency); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="4"><?= _l('perfex_saas_subtotal'); ?></th>
                                    <td><span class="subtotal"><?= app_format_money('0.00', $currency); ?></span></td>
                                </tr>
                                <tr>
                                    <th colspan="4"><?= _l('perfex_saas_module_subtotal'); ?></th>
                                    <td><span class="modules-subtotal"><?= app_format_money('0.00', $currency); ?></span>
                                    </td>
                                </tr>
                                <?php if (!empty($taxes)) : ?>
                                    <?php
                                    foreach ($taxes as $key => $tax) :
                                        $tax = explode('|', $tax);
                                        $tax_amount = (float)end($tax);
                                    ?>
                                        <tr>
                                            <th colspan="4"><?= $tax[0]; ?> (<?= $tax_amount; ?>%)</th>
                                            <td><span class="tax-subtotal" data-percent="<?= $tax_amount; ?>"><?= app_format_money('0.00', $currency); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <tr>
                                    <th colspan="4"><?= _l('perfex_saas_total'); ?></th>
                                    <td><strong><span id="total-amount"><?= app_format_money('0.00', $currency); ?> /
                                                <?= $billing_cycle; ?></span></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="tw-flex tw-justify-end tw-w-full">
                        <button class="btn btn-danger tw-w-full" onclick="return confirm('<?= _l('perfex_saas_confirm_customize_package'); ?>');"><?= _l('perfex_saas_upgrade'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>


<!-- Module Selection Modal -->
<div class="modal fade tw-z-20" id="moduleModal" tabindex="-1" role="dialog" aria-labelledby="moduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="">
                        <h3 class="modal-title" id="moduleModalLabel">
                            <?= _l('perfex_saas_module_marketplace_title'); ?>
                        </h3>
                        <p><?= _l('perfex_saas_module_marketplace_subtitle'); ?></p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= _l('close'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row perfex-saas-modules">
                    <!-- Example module card -->
                    <?php
                    foreach ($modules as $key => $module) :
                        $key = $module['system_name'];
                        $name = $module['custom_name'] ?? $key;
                        if ((empty($module['price']) && empty($package->metadata->limitations_unit_price->{$key}))) continue;
                        $price = $package->metadata->limitations_unit_price->{$key} ?? $module['price'];
                    ?>
                        <div class="col-md-4 tw-mb-3 perfex-saas-module-card">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <h4 class="card-title text-center"><?= $name; ?></h4>
                                        <p class="description truncate-text"><?= $module['description']; ?></p>
                                        <p class="card-text"><?= _l('perfex_saas_price'); ?>:
                                            <strong><?= app_format_money($price, $currency); ?>/<?= $billing_cycle; ?></strong>
                                        </p>
                                    </div>
                                    <div class="tw-flex tw-justify-end">
                                        <?php if (in_array($key, $package->modules)) : ?>
                                            <small class="btn btn-secondary disabled btn-sm" data-toggle="tooltip" data-title="<?= _l('perfex_saas_module_included_hint'); ?>" disabled><?= _l('perfex_saas_module_included'); ?> <i class="fa fa-question"></i></small>
                                        <?php else : ?>
                                            <button type="button" class="btn btn-primary add-module add-module-<?= $key; ?>" data-key="<?= $key; ?>" data-price="<?= $price; ?>" data-price-formatted="<?= app_format_money($price, $currency); ?>" data-name="<?= $name; ?>"><?= _l('perfex_saas_add'); ?></button>
                                            <button type="button" class="btn btn-danger remove-module" data-key="<?= $key; ?>" data-price="<?= $price; ?>" data-name="<?= $name; ?>" style="display: none;"><?= _l('perfex_saas_remove'); ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($allow_module_request) : ?>
                        <div class="col-md-12 tw-mb-3 tw-mt-8 perfex-saas-module-card">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <h4 class="card-title text-center"><?= _l('perfex_saas_request_module'); ?></h4>
                                        <p class="text-center"><?= _l('perfex_saas_request_module_desc'); ?></p>

                                    </div>
                                    <div class="tw-flex tw-justify-center">
                                        <a href="<?= $module_request_url; ?>" target="_blank" class="btn btn-info btn-full"><?= _l('perfex_saas_request_module_btn'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    const billingCycle = "<?= $billing_cycle; ?>";
    const basePrice = parseFloat(<?= $package->price; ?>);
    const discounts = <?= json_encode($discounts); ?>;
    const purchasedModules = <?= json_encode((array)$purchased_modules); ?>;

    const replaceNumberInString = (newNumber, originalString = "<?= app_format_money(0, $currency); ?>") => {
        // Use regular expressions to find and replace the number
        let pattern = /(\d+)(\.\d+)/; // Matches whole numbers with optional decimal part

        // Find the old number in the original string
        let matches = originalString.match(pattern);

        if (matches) {
            // Extract the matched number as a string
            let oldNumberStr = matches[0];

            // Replace the old number with the new number and reconstruct the string
            let newString = originalString.replace(pattern, function(match) {
                return match.replace(oldNumberStr, formatNumberWithCommas(newNumber).toString());
            });

            return newString;
        } else {
            return newNumber;
        }
    }

    const getDiscountedUnitPrice = (resources, unitPrice, newLimit) => {

        if (discounts?. [resources]) {
            let units = Object.keys(discounts[resources]).sort((a, b) => {
                return b - a
            });
            for (let index = 0; index < units.length; index++) {
                const level = parseInt(units[index]);

                document.querySelectorAll(`.discount.${resources} span`).forEach((v) => v.classList.remove('strike'));

                if (newLimit >= level) {
                    const discount = discounts[resources][units[index]];
                    const percent = parseFloat(discount.percent) / 100;
                    unitPrice = unitPrice - (percent * unitPrice);
                    document.querySelector(`.${resources+''+level}`).classList.add('strike');
                    break;
                }
            }
        }
        return unitPrice;
    }

    function formatNumberWithCommas(number) {
        // Convert the number to a string and split it at the decimal point (if present)
        let parts = number.toString().split(".");

        // Format the integer part with commas
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        // Join the integer and decimal parts (if present)
        return parts.join(".");
    }

    // JavaScript logic for calculating totals and managing modules
    const featureLimitInputs = document.querySelectorAll('.feature-limit');
    const unitPriceElements = document.querySelectorAll('td:nth-child(4)');
    const priceAdditionElements = document.querySelectorAll('.price-addition');
    const featureSubtotalElement = document.querySelector('.subtotal');
    const modulesSubtotalElement = document.querySelector('.modules-subtotal');
    const totalAmountElement = document.getElementById('total-amount');
    const taxElements = document.querySelectorAll('.tax-subtotal');


    let featureSubtotal = 0;
    let modulesSubtotal = 0;
    let taxSubtotal = 0;

    const setTotalAmount = (total) => {

        // Apply taxes
        taxSubtotal = 0;
        taxElements.forEach((tax) => {
            let taxAmount = (parseFloat(tax.dataset.percent) / 100) * total;
            taxSubtotal += taxAmount;
            tax.textContent = `${replaceNumberInString((taxAmount).toFixed(2))}`;
        });

        totalAmountElement.textContent =
            `${replaceNumberInString((total+taxSubtotal).toFixed(2))} / ${billingCycle}`;
    }

    featureLimitInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            const resources = input.dataset.id;
            const newLimit = parseInt(input.dataset.quantity ?? (input.value.length ? input.value : "0"));
            let unitPrice = parseFloat(input.dataset.unitPrice);
            // get discounted price
            unitPrice = getDiscountedUnitPrice(resources, unitPrice, newLimit);

            const priceAddition = unitPrice * newLimit;
            priceAdditionElements[index].textContent = `${replaceNumberInString(priceAddition.toFixed(2))}`;
            priceAdditionElements[index].dataset.price = priceAddition;

            // Calculate feature subtotal
            featureSubtotal = 0;
            priceAdditionElements.forEach(element => {
                featureSubtotal += parseFloat(element.dataset.price);
            });
            featureSubtotalElement.textContent =
                `${replaceNumberInString(featureSubtotal.toFixed(2))}`;


            // Calculate total amount
            let _total = featureSubtotal + modulesSubtotal + basePrice;
            setTotalAmount(_total);
        });
    });

    const addModuleButtons = document.querySelectorAll('.add-module');
    const removeModuleButtons = document.querySelectorAll('.remove-module');
    const paidModules = document.querySelector("#paid-modules");

    addModuleButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            const originalPrice = parseFloat(button.dataset.price);
            const price = getDiscountedUnitPrice(button.dataset.key, originalPrice, 1);
            modulesSubtotal += price;
            modulesSubtotalElement.textContent =
                `${replaceNumberInString(modulesSubtotal.toFixed(2))}`;

            // Calculate total amount
            let _total = featureSubtotal + modulesSubtotal + basePrice;
            setTotalAmount(_total);

            // Toggle buttons
            button.style.display = 'none';
            removeModuleButtons[index].style.display = 'inline-block';

            // Add to table row
            paidModules.insertAdjacentHTML('beforebegin', `
            <tr data-key="${button.dataset.key}">
                <td class="!tw-max-w-xs">${button.dataset.name}</td>
                <td><?= _l('perfex_saas_unlimited'); ?>
                    <input value="${button.dataset.key}" name="purchased_modules[]" type="hidden" data-unit-price="${button.dataset.price}" data-quantity="1"  class="feature-limit" />
                </td>
                <td><?= $na; ?></td>
                <td class="text-lowercase">${button.dataset.priceFormatted}</td>
                <td class="text-lowercase"><span class="price-addition" data-price="${button.dataset.price}">${button.dataset.priceFormatted}</span>
                </td>
            </tr>
            `);
        });
    });

    removeModuleButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            const originalPrice = parseFloat(button.dataset.price);
            const price = getDiscountedUnitPrice(button.dataset.key, originalPrice, 1);
            modulesSubtotal -= price;
            modulesSubtotalElement.textContent =
                `${replaceNumberInString(modulesSubtotal.toFixed(2))}`;

            // Calculate total amount
            let _total = featureSubtotal + modulesSubtotal + basePrice;
            setTotalAmount(_total);

            // Toggle buttons
            button.style.display = 'none';
            addModuleButtons[index].style.display = 'inline-block';

            // Remove the row
            document.querySelector(`tr[data-key='${button.dataset.key}']`).remove();
        });
    });

    // Trigger summation of features using js
    document.querySelectorAll(".feature-limit").forEach((input) => input.dispatchEvent(new Event('input', {
        bubbles: true
    })));

    // Trigger summation of modules using js
    if (purchasedModules.length)
        document.querySelectorAll("button.add-module-" + purchasedModules.join(", button.add-module-")).forEach((button) =>
            button.dispatchEvent(
                new Event('click', {
                    bubbles: true
                })));
</script>