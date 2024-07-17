<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Financeiro_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        // a acrescentar
    }


    /**
     * obter_detalhes_tipo_conta
     * @param  integer $id    member group id
     * @param  array  $where
     * @return object
     */
    public function obter_detalhes_tipo_conta()
    {
        $account_type_details = hooks()->apply_filters('before_get_account_type_details', [
            [
                'id'                => 1,
                'account_type_id'   => 1,
                'name'              => _l('acc_accounts_receivable'),
                'note'              => _l('acc_accounts_receivable_note'),
                'order'             => 1,
            ],
            [
                'id'                => 2,
                'account_type_id'   => 2,
                'name'              => _l('acc_allowance_for_bad_debts'),
                'note'              => _l('acc_allowance_for_bad_debts_note'),
                'order'             => 2,
            ],
            [
                'id'                => 3,
                'account_type_id'   => 2,
                'name'              => _l('acc_assets_available_for_sale'),
                'note'              => _l('acc_assets_available_for_sale_note'),
                'order'             => 3,
            ],
            [
                'id'                => 4,
                'account_type_id'   => 2,
                'name'              => _l('acc_development_costs'),
                'note'              => _l('acc_development_costs_note'),
                'order'             => 4,
            ],
            [
                'id'                => 141,
                'account_type_id'   => 2,
                'name'              => _l('acc_employee_cash_advances'),
                'note'              => _l('acc_employee_cash_advances_note'),
                'order'             => 5,
            ],
            [
                'id'                => 5,
                'account_type_id'   => 2,
                'name'              => _l('acc_inventory'),
                'note'              => _l('acc_inventory_note'),
                'order'             => 5,
            ],
            [
                'id'                => 6,
                'account_type_id'   => 2,
                'name'              => _l('acc_investments_other'),
                'note'              => _l('acc_investments_other_note'),
                'order'             => 6,
            ],
            [
                'id'                => 7,
                'account_type_id'   => 2,
                'name'              => _l('acc_loans_to_officers'),
                'note'              => _l('acc_loans_to_officers_note'),
                'order'             => 7,
            ],
            [
                'id'                => 8,
                'account_type_id'   => 2,
                'name'              => _l('acc_loans_to_others'),
                'note'              => _l('acc_loans_to_others_note'),
                'order'             => 8,
            ],
            [
                'id'                => 9,
                'account_type_id'   => 2,
                'name'              => _l('acc_loans_to_shareholders'),
                'note'              => _l('acc_loans_to_shareholders_note'),
                'order'             => 9,
            ],
            [
                'id'                => 10,
                'account_type_id'   => 2,
                'name'              => _l('acc_other_current_assets'),
                'note'              => _l('acc_other_current_assets_note'),
                'order'             => 10,
            ],
            [
                'id'                => 11,
                'account_type_id'   => 2,
                'name'              => _l('acc_prepaid_expenses'),
                'note'              => _l('acc_prepaid_expenses_note'),
                'order'             => 11,
            ],
            [
                'id'                => 12,
                'account_type_id'   => 2,
                'name'              => _l('acc_retainage'),
                'note'              => _l('acc_retainage_note'),
                'order'             => 12,
            ],
            [
                'id'                => 13,
                'account_type_id'   => 2,
                'name'              => _l('acc_undeposited_funds'),
                'note'              => _l('acc_undeposited_funds_note'),
                'order'             => 13,
            ],
            [
                'id'                => 14,
                'account_type_id'   => 3,
                'name'              => _l('acc_bank'),
                'note'              => _l('acc_bank_note'),
                'order'             => 14,
            ],
            [
                'id'                => 15,
                'account_type_id'   => 3,
                'name'              => _l('acc_cash_and_cash_equivalents'),
                'note'              => _l('acc_cash_and_cash_equivalents_note'),
                'order'             => 15,
            ],
            [
                'id'                => 16,
                'account_type_id'   => 3,
                'name'              => _l('acc_cash_on_hand'),
                'note'              => _l('acc_cash_on_hand_note'),
                'order'             => 16,
            ],
            [
                'id'                => 17,
                'account_type_id'   => 3,
                'name'              => _l('acc_client_trust_account'),
                'note'              => _l('acc_client_trust_account_note'),
                'order'             => 17,
            ],
            [
                'id'                => 18,
                'account_type_id'   => 3,
                'name'              => _l('acc_money_market'),
                'note'              => _l('acc_money_market_note'),
                'order'             => 18,
            ],
            [
                'id'                => 19,
                'account_type_id'   => 3,
                'name'              => _l('acc_rents_held_in_trust'),
                'note'              => _l('acc_rents_held_in_trust_note'),
                'order'             => 19,
            ],
            [
                'id'                => 20,
                'account_type_id'   => 3,
                'name'              => _l('acc_savings'),
                'note'              => _l('acc_savings_note'),
                'order'             => 20,
            ],
            [
                'id'                => 21,
                'account_type_id'   => 4,
                'name'              => _l('acc_accumulated_depletion'),
                'note'              => _l('acc_accumulated_depletion_note'),
                'order'             => 21,
            ],
            [
                'id'                => 22,
                'account_type_id'   => 4,
                'name'              => _l('acc_accumulated_depreciation_on_property_plant_and_equipment'),
                'note'              => _l('acc_accumulated_depreciation_on_property_plant_and_equipment_note'),
                'order'             => 22,
            ],
            [
                'id'                => 23,
                'account_type_id'   => 4,
                'name'              => _l('acc_buildings'),
                'note'              => _l('acc_buildings_note'),
                'order'             => 23,
            ],
            [
                'id'                => 24,
                'account_type_id'   => 4,
                'name'              => _l('acc_depletable_assets'),
                'note'              => _l('acc_depletable_assets_note'),
                'order'             => 24,
            ],
            [
                'id'                => 25,
                'account_type_id'   => 4,
                'name'              => _l('acc_furniture_and_fixtures'),
                'note'              => _l('acc_furniture_and_fixtures_note'),
                'order'             => 25,
            ],
            [
                'id'                => 26,
                'account_type_id'   => 4,
                'name'              => _l('acc_land'),
                'note'              => _l('acc_land_note'),
                'order'             => 26,
            ],
            [
                'id'                => 27,
                'account_type_id'   => 4,
                'name'              => _l('acc_leasehold_improvements'),
                'note'              => _l('acc_leasehold_improvements_note'),
                'order'             => 27,
            ],
            [
                'id'                => 28,
                'account_type_id'   => 4,
                'name'              => _l('acc_machinery_and_equipment'),
                'note'              => _l('acc_machinery_and_equipment_note'),
                'order'             => 28,
            ],
            [
                'id'                => 29,
                'account_type_id'   => 4,
                'name'              => _l('acc_other_fixed_assets'),
                'note'              => _l('acc_other_fixed_assets_note'),
                'order'             => 29,
            ],
            [
                'id'                => 30,
                'account_type_id'   => 4,
                'name'              => _l('acc_vehicles'),
                'note'              => _l('acc_vehicles_note'),
                'order'             => 30,
            ],
            [
                'id'                => 31,
                'account_type_id'   => 5,
                'name'              => _l('acc_accumulated_amortisation_of_non_current_assets'),
                'note'              => _l('acc_accumulated_amortisation_of_non_current_assets_note'),
                'order'             => 31,
            ],
            [
                'id'                => 32,
                'account_type_id'   => 5,
                'name'              => _l('acc_assets_held_for_sale'),
                'note'              => _l('acc_assets_held_for_sale_note'),
                'order'             => 32,
            ],
            [
                'id'                => 33,
                'account_type_id'   => 5,
                'name'              => _l('acc_deferred_tax'),
                'note'              => _l('acc_deferred_tax_note'),
                'order'             => 33,
            ],
            [
                'id'                => 34,
                'account_type_id'   => 5,
                'name'              => _l('acc_goodwill'),
                'note'              => _l('acc_goodwill_note'),
                'order'             => 34,
            ],
            [
                'id'                => 35,
                'account_type_id'   => 5,
                'name'              => _l('acc_intangible_assets'),
                'note'              => _l('acc_intangible_assets_note'),
                'order'             => 35,
            ],
            [
                'id'                => 36,
                'account_type_id'   => 5,
                'name'              => _l('acc_lease_buyout'),
                'note'              => _l('acc_lease_buyout_note'),
                'order'             => 36,
            ],
            [
                'id'                => 37,
                'account_type_id'   => 5,
                'name'              => _l('acc_licences'),
                'note'              => _l('acc_licences_note'),
                'order'             => 37,
            ],
            [
                'id'                => 38,
                'account_type_id'   => 5,
                'name'              => _l('acc_long_term_investments'),
                'note'              => _l('acc_long_term_investments_note'),
                'order'             => 38,
            ],
            [
                'id'                => 39,
                'account_type_id'   => 5,
                'name'              => _l('acc_organisational_costs'),
                'note'              => _l('acc_organisational_costs_note'),
                'order'             => 39,
            ],
            [
                'id'                => 40,
                'account_type_id'   => 5,
                'name'              => _l('acc_other_non_current_assets'),
                'note'              => _l('acc_other_non_current_assets_note'),
                'order'             => 40,
            ],
            [
                'id'                => 41,
                'account_type_id'   => 5,
                'name'              => _l('acc_security_deposits'),
                'note'              => _l('acc_security_deposits_note'),
                'order'             => 41,
            ],
            [
                'id'                => 42,
                'account_type_id'   => 6,
                'name'              => _l('acc_accounts_payable'),
                'note'              => _l('acc_accounts_payable_note'),
                'order'             => 42,
            ],
            [
                'id'                => 43,
                'account_type_id'   => 7,
                'name'              => _l('acc_credit_card'),
                'note'              => _l('acc_credit_card_note'),
                'order'             => 43,
            ],
            [
                'id'                => 44,
                'account_type_id'   => 8,
                'name'              => _l('acc_accrued_liabilities'),
                'note'              => _l('acc_accrued_liabilities_note'),
                'order'             => 44,
            ],
            [
                'id'                => 45,
                'account_type_id'   => 8,
                'name'              => _l('acc_client_trust_accounts_liabilities'),
                'note'              => _l('acc_client_trust_accounts_liabilities_note'),
                'order'             => 45,
            ],
            [
                'id'                => 46,
                'account_type_id'   => 8,
                'name'              => _l('acc_current_tax_liability'),
                'note'              => _l('acc_current_tax_liability_note'),
                'order'             => 46,
            ],
            [
                'id'                => 47,
                'account_type_id'   => 8,
                'name'              => _l('acc_current_portion_of_obligations_under_finance_leases'),
                'note'              => _l('acc_current_portion_of_obligations_under_finance_leases_note'),
                'order'             => 47,
            ],
            [
                'id'                => 48,
                'account_type_id'   => 8,
                'name'              => _l('acc_dividends_payable'),
                'note'              => _l('acc_dividends_payable_note'),
                'order'             => 48,
            ],
            [
                'id'                => 50,
                'account_type_id'   => 8,
                'name'              => _l('acc_income_tax_payable'),
                'note'              => _l('acc_income_tax_payable_note'),
                'order'             => 50,
            ],
            [
                'id'                => 51,
                'account_type_id'   => 8,
                'name'              => _l('acc_insurance_payable'),
                'note'              => _l('acc_insurance_payable_note'),
                'order'             => 51,
            ],
            [
                'id'                => 52,
                'account_type_id'   => 8,
                'name'              => _l('acc_line_of_credit'),
                'note'              => _l('acc_line_of_credit_note'),
                'order'             => 52,
            ],
            [
                'id'                => 53,
                'account_type_id'   => 8,
                'name'              => _l('acc_loan_payable'),
                'note'              => _l('acc_loan_payable_note'),
                'order'             => 53,
            ],
            [
                'id'                => 54,
                'account_type_id'   => 8,
                'name'              => _l('acc_other_current_liabilities'),
                'note'              => _l('acc_other_current_liabilities_note'),
                'order'             => 54,
            ],
            [
                'id'                => 55,
                'account_type_id'   => 8,
                'name'              => _l('acc_payroll_clearing'),
                'note'              => _l('acc_payroll_clearing_note'),
                'order'             => 55,
            ],
            [
                'id'                => 56,
                'account_type_id'   => 8,
                'name'              => _l('acc_payroll_liabilities'),
                'note'              => _l('acc_payroll_liabilities_note'),
                'order'             => 56,
            ],
            [
                'id'                => 58,
                'account_type_id'   => 8,
                'name'              => _l('acc_prepaid_expenses_payable'),
                'note'              => _l('acc_prepaid_expenses_payable_note'),
                'order'             => 58,
            ],
            [
                'id'                => 59,
                'account_type_id'   => 8,
                'name'              => _l('acc_rents_in_trust_liability'),
                'note'              => _l('acc_rents_in_trust_liability_note'),
                'order'             => 59,
            ],
            [
                'id'                => 60,
                'account_type_id'   => 8,
                'name'              => _l('acc_sales_and_service_tax_payable'),
                'note'              => _l('acc_sales_and_service_tax_payable_note'),
                'order'             => 60,
            ],
            [
                'id'                => 61,
                'account_type_id'   => 9,
                'name'              => _l('acc_accrued_holiday_payable'),
                'note'              => _l('acc_accrued_holiday_payable_note'),
                'order'             => 61,
            ],
            [
                'id'                => 62,
                'account_type_id'   => 9,
                'name'              => _l('acc_accrued_non_current_liabilities'),
                'note'              => _l('acc_accrued_non_current_liabilities_note'),
                'order'             => 62,
            ],
            [
                'id'                => 63,
                'account_type_id'   => 9,
                'name'              => _l('acc_liabilities_related_to_assets_held_for_sale'),
                'note'              => _l('acc_liabilities_related_to_assets_held_for_sale_note'),
                'order'             => 63,
            ],
            [
                'id'                => 64,
                'account_type_id'   => 9,
                'name'              => _l('acc_long_term_debt'),
                'note'              => _l('acc_long_term_debt_note'),
                'order'             => 64,
            ],
            [
                'id'                => 65,
                'account_type_id'   => 9,
                'name'              => _l('acc_notes_payable'),
                'note'              => _l('acc_notes_payable_note'),
                'order'             => 65,
            ],
            [
                'id'                => 66,
                'account_type_id'   => 9,
                'name'              => _l('acc_other_non_current_liabilities'),
                'note'              => _l('acc_other_non_current_liabilities_note'),
                'order'             => 66,
            ],
            [
                'id'                => 67,
                'account_type_id'   => 9,
                'name'              => _l('acc_shareholder_potes_payable'),
                'note'              => _l('acc_shareholder_potes_payable_note'),
                'order'             => 67,
            ],
            [
                'id'                => 68,
                'account_type_id'   => 10,
                'name'              => _l('acc_accumulated_adjustment'),
                'note'              => _l('acc_accumulated_adjustment_note'),
                'order'             => 68,
            ],
            [
                'id'                => 69,
                'account_type_id'   => 10,
                'name'              => _l('acc_dividend_disbursed'),
                'note'              => _l('acc_dividend_disbursed_note'),
                'order'             => 69,
            ],
            [
                'id'                => 70,
                'account_type_id'   => 10,
                'name'              => _l('acc_equity_in_earnings_of_subsidiaries'),
                'note'              => _l('acc_equity_in_earnings_of_subsidiaries_note'),
                'order'             => 70,
            ],
            [
                'id'                => 71,
                'account_type_id'   => 10,
                'name'              => _l('acc_opening_balance_equity'),
                'note'              => _l('acc_opening_balance_equity_note'),
                'order'             => 71,
            ],
            [
                'id'                => 72,
                'account_type_id'   => 10,
                'name'              => _l('acc_ordinary_shares'),
                'note'              => _l('acc_ordinary_shares_note'),
                'order'             => 72,
            ],
            [
                'id'                => 73,
                'account_type_id'   => 10,
                'name'              => _l('acc_other_comprehensive_income'),
                'note'              => _l('acc_other_comprehensive_income_note'),
                'order'             => 73,
            ],
            [
                'id'                => 74,
                'account_type_id'   => 10,
                'name'              => _l('acc_owner_equity'),
                'note'              => _l('acc_owner_equity_note'),
                'order'             => 74,
            ],
            [
                'id'                => 75,
                'account_type_id'   => 10,
                'name'              => _l('acc_paid_in_capital_or_surplus'),
                'note'              => _l('acc_paid_in_capital_or_surplus_note'),
                'order'             => 75,
            ],
            [
                'id'                => 76,
                'account_type_id'   => 10,
                'name'              => _l('acc_partner_contributions'),
                'note'              => _l('acc_partner_contributions_note'),
                'order'             => 76,
            ],
            [
                'id'                => 77,
                'account_type_id'   => 10,
                'name'              => _l('acc_partner_distributions'),
                'note'              => _l('acc_partner_distributions_note'),
                'order'             => 77,
            ],
            [
                'id'                => 78,
                'account_type_id'   => 10,
                'name'              => _l('acc_partner_equity'),
                'note'              => _l('acc_partner_equity_note'),
                'order'             => 78,
            ],
            [
                'id'                => 79,
                'account_type_id'   => 10,
                'name'              => _l('acc_preferred_shares'),
                'note'              => _l('acc_preferred_shares_note'),
                'order'             => 79,
            ],
            [
                'id'                => 80,
                'account_type_id'   => 10,
                'name'              => _l('acc_retained_earnings'),
                'note'              => _l('acc_retained_earnings_note'),
                'order'             => 80,
            ],
            [
                'id'                => 81,
                'account_type_id'   => 10,
                'name'              => _l('acc_share_capital'),
                'note'              => _l('acc_share_capital_note'),
                'order'             => 81,
            ],
            [
                'id'                => 82,
                'account_type_id'   => 10,
                'name'              => _l('acc_treasury_shares'),
                'note'              => _l('acc_treasury_shares_note'),
                'order'             => 82,
            ],
            [
                'id'                => 83,
                'account_type_id'   => 11,
                'name'              => _l('acc_discounts_refunds_given'),
                'note'              => _l('acc_discounts_refunds_given_note'),
                'order'             => 83,
            ],
            [
                'id'                => 84,
                'account_type_id'   => 11,
                'name'              => _l('acc_non_profit_income'),
                'note'              => _l('acc_non_profit_income_note'),
                'order'             => 84,
            ],
            [
                'id'                => 85,
                'account_type_id'   => 11,
                'name'              => _l('acc_other_primary_income'),
                'note'              => _l('acc_other_primary_income_note'),
                'order'             => 85,
            ],
            [
                'id'                => 86,
                'account_type_id'   => 11,
                'name'              => _l('acc_revenue_general'),
                'note'              => _l('acc_revenue_general_note'),
                'order'             => 86,
            ],
            [
                'id'                => 87,
                'account_type_id'   => 11,
                'name'              => _l('acc_sales_retail'),
                'note'              => _l('acc_sales_retail_note'),
                'order'             => 87,
            ],
            [
                'id'                => 88,
                'account_type_id'   => 11,
                'name'              => _l('acc_sales_wholesale'),
                'note'              => _l('acc_sales_wholesale_note'),
                'order'             => 88,
            ],
            [
                'id'                => 89,
                'account_type_id'   => 11,
                'name'              => _l('acc_sales_of_product_income'),
                'note'              => _l('acc_sales_of_product_income_note'),
                'order'             => 89,
            ],
            [
                'id'                => 90,
                'account_type_id'   => 11,
                'name'              => _l('acc_service_fee_income'),
                'note'              => _l('acc_service_fee_income_note'),
                'order'             => 90,
            ],
            [
                'id'                => 91,
                'account_type_id'   => 11,
                'name'              => _l('acc_unapplied_cash_payment_income'),
                'note'              => _l('acc_unapplied_cash_payment_income_note'),
                'order'             => 91,
            ],
            [
                'id'                => 92,
                'account_type_id'   => 12,
                'name'              => _l('acc_dividend_income'),
                'note'              => _l('acc_dividend_income_note'),
                'order'             => 92,
            ],
            [
                'id'                => 93,
                'account_type_id'   => 12,
                'name'              => _l('acc_interest_earned'),
                'note'              => _l('acc_interest_earned_note'),
                'order'             => 93,
            ],
            [
                'id'                => 94,
                'account_type_id'   => 12,
                'name'              => _l('acc_loss_on_disposal_of_assets'),
                'note'              => _l('acc_loss_on_disposal_of_assets_note'),
                'order'             => 94,
            ],
            [
                'id'                => 95,
                'account_type_id'   => 12,
                'name'              => _l('acc_other_investment_income'),
                'note'              => _l('acc_other_investment_income_note'),
                'order'             => 95,
            ],
            [
                'id'                => 96,
                'account_type_id'   => 12,
                'name'              => _l('acc_other_miscellaneous_income'),
                'note'              => _l('acc_other_miscellaneous_income_note'),
                'order'             => 96,
            ],
            [
                'id'                => 97,
                'account_type_id'   => 12,
                'name'              => _l('acc_other_operating_income'),
                'note'              => _l('acc_other_operating_income_note'),
                'order'             => 97,
            ],
            [
                'id'                => 98,
                'account_type_id'   => 12,
                'name'              => _l('acc_tax_exempt_interest'),
                'note'              => _l('acc_tax_exempt_interest_note'),
                'order'             => 98,
            ],
            [
                'id'                => 99,
                'account_type_id'   => 12,
                'name'              => _l('acc_unrealised_loss_on_securities_net_of_tax'),
                'note'              => _l('acc_unrealised_loss_on_securities_net_of_tax_note'),
                'order'             => 99,
            ],
            [
                'id'                => 100,
                'account_type_id'   => 13,
                'name'              => _l('acc_cost_of_labour_cos'),
                'note'              => _l('acc_cost_of_labour_cos_note'),
                'order'             => 100,
            ],
            [
                'id'                => 101,
                'account_type_id'   => 13,
                'name'              => _l('acc_equipment_rental_cos'),
                'note'              => _l('acc_equipment_rental_cos_note'),
                'order'             => 101,
            ],
            [
                'id'                => 102,
                'account_type_id'   => 13,
                'name'              => _l('acc_freight_and_delivery_cos'),
                'note'              => _l('acc_freight_and_delivery_cos_note'),
                'order'             => 102,
            ],
            [
                'id'                => 103,
                'account_type_id'   => 13,
                'name'              => _l('acc_other_costs_of_sales_cos'),
                'note'              => _l('acc_other_costs_of_sales_cos_note'),
                'order'             => 103,
            ],
            [
                'id'                => 104,
                'account_type_id'   => 13,
                'name'              => _l('acc_supplies_and_materials_cos'),
                'note'              => _l('acc_supplies_and_materials_cos_note'),
                'order'             => 104,
            ],
            [
                'id'                => 105,
                'account_type_id'   => 14,
                'name'              => _l('acc_advertising_promotional'),
                'note'              => _l('acc_advertising_promotional_note'),
                'order'             => 105,
            ],
            [
                'id'                => 106,
                'account_type_id'   => 14,
                'name'              => _l('acc_amortisation_expense'),
                'note'              => _l('acc_amortisation_expense_note'),
                'order'             => 106,
            ],
            [
                'id'                => 107,
                'account_type_id'   => 14,
                'name'              => _l('acc_auto'),
                'note'              => _l('acc_auto_note'),
                'order'             => 107,
            ],
            [
                'id'                => 108,
                'account_type_id'   => 14,
                'name'              => _l('acc_bad_debts'),
                'note'              => _l('acc_bad_debts_note'),
                'order'             => 108,
            ],
            [
                'id'                => 109,
                'account_type_id'   => 14,
                'name'              => _l('acc_bank_charges'),
                'note'              => _l('acc_bank_charges_note'),
                'order'             => 109,
            ],
            [
                'id'                => 110,
                'account_type_id'   => 14,
                'name'              => _l('acc_charitable_contributions'),
                'note'              => _l('acc_charitable_contributions_note'),
                'order'             => 110,
            ],
            [
                'id'                => 111,
                'account_type_id'   => 14,
                'name'              => _l('acc_commissions_and_fees'),
                'note'              => _l('acc_commissions_and_fees_note'),
                'order'             => 111,
            ],
            [
                'id'                => 112,
                'account_type_id'   => 14,
                'name'              => _l('acc_cost_of_labour'),
                'note'              => _l('acc_cost_of_labour_note'),
                'order'             => 112,
            ],
            [
                'id'                => 113,
                'account_type_id'   => 14,
                'name'              => _l('acc_dues_and_subscriptions'),
                'note'              => _l('acc_dues_and_subscriptions_note'),
                'order'             => 113,
            ],
            [
                'id'                => 114,
                'account_type_id'   => 14,
                'name'              => _l('acc_equipment_rental'),
                'note'              => _l('acc_equipment_rental_note'),
                'order'             => 114,
            ],
            [
                'id'                => 115,
                'account_type_id'   => 14,
                'name'              => _l('acc_finance_costs'),
                'note'              => _l('acc_finance_costs_note'),
                'order'             => 115,
            ],
            [
                'id'                => 116,
                'account_type_id'   => 14,
                'name'              => _l('acc_income_tax_expense'),
                'note'              => _l('acc_income_tax_expense_note'),
                'order'             => 116,
            ],
            [
                'id'                => 117,
                'account_type_id'   => 14,
                'name'              => _l('acc_insurance'),
                'note'              => _l('acc_insurance_note'),
                'order'             => 117,
            ],
            [
                'id'                => 118,
                'account_type_id'   => 14,
                'name'              => _l('acc_interest_paid'),
                'note'              => _l('acc_interest_paid_note'),
                'order'             => 118,
            ],
            [
                'id'                => 119,
                'account_type_id'   => 14,
                'name'              => _l('acc_legal_and_professional_fees'),
                'note'              => _l('acc_legal_and_professional_fees_note'),
                'order'             => 119,
            ],
            [
                'id'                => 120,
                'account_type_id'   => 14,
                'name'              => _l('acc_loss_on_discontinued_operations_net_of_tax'),
                'note'              => _l('acc_loss_on_discontinued_operations_net_of_tax_note'),
                'order'             => 120,
            ],
            [
                'id'                => 121,
                'account_type_id'   => 14,
                'name'              => _l('acc_management_compensation'),
                'note'              => _l('acc_management_compensation_note'),
                'order'             => 121,
            ],
            [
                'id'                => 122,
                'account_type_id'   => 14,
                'name'              => _l('acc_meals_and_entertainment'),
                'note'              => _l('acc_meals_and_entertainment_note'),
                'order'             => 122,
            ],
            [
                'id'                => 123,
                'account_type_id'   => 14,
                'name'              => _l('acc_office_general_administrative_expenses'),
                'note'              => _l('acc_office_general_administrative_expenses_note'),
                'order'             => 123,
            ],
            [
                'id'                => 124,
                'account_type_id'   => 14,
                'name'              => _l('acc_other_miscellaneous_service_cost'),
                'note'              => _l('acc_other_miscellaneous_service_cost_note'),
                'order'             => 124,
            ],
            [
                'id'                => 125,
                'account_type_id'   => 14,
                'name'              => _l('acc_other_selling_expenses'),
                'note'              => _l('acc_other_selling_expenses_note'),
                'order'             => 125,
            ],
            [
                'id'                => 126,
                'account_type_id'   => 14,
                'name'              => _l('acc_payroll_expenses'),
                'note'              => _l('acc_payroll_expenses_note'),
                'order'             => 126,
            ],
            [
                'id'                => 127,
                'account_type_id'   => 14,
                'name'              => _l('acc_rent_or_lease_of_buildings'),
                'note'              => _l('acc_rent_or_lease_of_buildings_note'),
                'order'             => 127,
            ],
            [
                'id'                => 128,
                'account_type_id'   => 14,
                'name'              => _l('acc_repair_and_maintenance'),
                'note'              => _l('acc_repair_and_maintenance_note'),
                'order'             => 128,
            ],
            [
                'id'                => 129,
                'account_type_id'   => 14,
                'name'              => _l('acc_shipping_and_delivery_expense'),
                'note'              => _l('acc_shipping_and_delivery_expense_note'),
                'order'             => 129,
            ],
            [
                'id'                => 130,
                'account_type_id'   => 14,
                'name'              => _l('acc_supplies_and_materials'),
                'note'              => _l('acc_supplies_and_materials_note'),
                'order'             => 130,
            ],
            [
                'id'                => 131,
                'account_type_id'   => 14,
                'name'              => _l('acc_taxes_paid'),
                'note'              => _l('acc_taxes_paid_note'),
                'order'             => 131,
            ],
            [
                'id'                => 132,
                'account_type_id'   => 14,
                'name'              => _l('acc_travel_expenses_general_and_admin_expenses'),
                'note'              => _l('acc_travel_expenses_general_and_admin_expenses_note'),
                'order'             => 132,
            ],
            [
                'id'                => 133,
                'account_type_id'   => 14,
                'name'              => _l('acc_travel_expenses_selling_expense'),
                'note'              => _l('acc_travel_expenses_selling_expense_note'),
                'order'             => 133,
            ],
            [
                'id'                => 134,
                'account_type_id'   => 14,
                'name'              => _l('acc_unapplied_cash_bill_payment_expense'),
                'note'              => _l('acc_unapplied_cash_bill_payment_expense_note'),
                'order'             => 134,
            ],
            [
                'id'                => 135,
                'account_type_id'   => 14,
                'name'              => _l('acc_utilities'),
                'note'              => _l('acc_utilities_note'),
                'order'             => 135,
            ],
            [
                'id'                => 136,
                'account_type_id'   => 15,
                'name'              => _l('acc_amortisation'),
                'note'              => _l('acc_amortisation_note'),
                'order'             => 136,
            ],
            [
                'id'                => 137,
                'account_type_id'   => 15,
                'name'              => _l('acc_depreciation'),
                'note'              => _l('acc_depreciation_note'),
                'order'             => 137,
            ],
            [
                'id'                => 138,
                'account_type_id'   => 15,
                'name'              => _l('acc_exchange_gain_or_loss'),
                'note'              => _l('acc_exchange_gain_or_loss_note'),
                'order'             => 138,
            ],
            [
                'id'                => 139,
                'account_type_id'   => 15,
                'name'              => _l('acc_other_expense'),
                'note'              => _l('acc_other_expense_note'),
                'order'             => 139,
            ],
            [
                'id'                => 140,
                'account_type_id'   => 15,
                'name'              => _l('acc_penalties_and_settlements'),
                'note'              => _l('acc_penalties_and_settlements_note'),
                'order'             => 140,
            ],
        ]);

        usort($account_type_details, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        $account_type_details_2 = $this->db->get(db_prefix().'acc_account_type_details')->result_array();

        return array_merge($account_type_details, $account_type_details_2);
    }

    /**
     * get accounts
     * @param  integer $id    member group id
     * @param  array  $where
     * @return object
     */
    public function obter_contas($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'fin_planocontas')->row();
        }

        $this->db->where($where);
        $this->db->where('active', 1);
        $this->db->order_by('account_type_id,account_detail_type_id', 'desc');
        $contas = $this->db->get(db_prefix() . 'fin_planocontas')->result_array();

        $tipos_conta = $this->financeiro_model->obter_tipo_contas();
        $detalhes_tipo = $this->financeiro_model->obter_detalhes_tipo_conta();

        $tipo_conta_nome = [];
        $detalhes_tipo_nome = [];

        foreach ($tipos_conta as $key => $value) {
            $tipo_conta_nome[$value['id']] = $value['name'];
        }

        foreach ($detalhes_tipo as $key => $value) {
            $detalhes_tipo_nome[$value['id']] = $value['name'];
        }

        foreach ($contas as $key => $value) {
            if($value['name'] == '' && $value['key_name'] != ''){
                $contas[$key]['name'] = _l($value['key_name']);
            }
            
            $_account_type_name = isset( $tipo_conta_nome[$value['account_type_id']]) ?  $tipo_conta_nome[$value['account_type_id']] : '';
            $_detail_type_name = isset($detalhes_tipo_nome[$value['account_detail_type_id']]) ? $detalhes_tipo_nome[$value['account_detail_type_id']] : '';
            $contas[$key]['account_type_name'] = $_account_type_name;
            $contas[$key]['detail_type_name'] = $_detail_type_name;
        }

        return $contas;
    }

    /**
     * Obter tipos de Conta
     * @param  integer $id    member group id
     * @param  array  $where
     * @return object
     */
    public function obter_tipos_conta()
    {
        $tipos_de_conta = hooks()->apply_filters('before_get_account_types', [
            [
                'id'             => 1,
                'name'           => _l('acc_accounts_receivable'),
                'order'          => 1,
            ],
            [
                'id'             => 2,
                'name'           => _l('acc_current_assets'),
                'order'          => 2,
            ],
            [
                'id'             => 3,
                'name'           => _l('acc_cash_and_cash_equivalents'),
                'order'          => 3,
            ],
            [
                'id'             => 4,
                'name'           => _l('acc_fixed_assets'),
                'order'          => 4,
            ],
            [
                'id'             => 5,
                'name'           => _l('acc_non_current_assets'),
                'order'          => 5,
            ],
            [
                'id'             => 6,
                'name'           => _l('acc_accounts_payable'),
                'order'          => 6,
            ],
            [
                'id'             => 7,
                'name'           => _l('acc_credit_card'),
                'order'          => 7,
            ],
            [
                'id'             => 8,
                'name'           => _l('acc_current_liabilities'),
                'order'          => 8,
            ],
            [
                'id'             => 9,
                'name'           => _l('acc_non_current_liabilities'),
                'order'          => 9,
            ],
            [
                'id'             => 10,
                'name'           => _l('acc_owner_equity'),
                'order'          => 10,
            ],
            [
                'id'             => 11,
                'name'           => _l('acc_income'),
                'order'          => 11,
            ],
            [
                'id'             => 12,
                'name'           => _l('acc_other_income'),
                'order'          => 12,
            ],
            [
                'id'             => 13,
                'name'           => _l('acc_cost_of_sales'),
                'order'          => 13,
            ],
            [
                'id'             => 14,
                'name'           => _l('acc_expenses'),
                'order'          => 14,
            ],
            [
                'id'             => 15,
                'name'           => _l('acc_other_expense'),
                'order'          => 15,
            ],
        ]);

        usort($account_types, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $account_types;
    }

}
