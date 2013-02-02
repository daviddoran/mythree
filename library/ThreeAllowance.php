<?php

class AllowanceParseException extends Exception {}

class ThreeAllowance {
    /**
     * @var int
     */
    public $date = null;
    /**
     * @var int
     */
    public $mobile_data = 0;
    /**
     * @var int
     */
    public $three_to_three_calls = 0;
    /**
     * @var int
     */
    public $flexi_units = 0;
    /**
     * @var int
     */
    public $evening_weekend_minutes = 0;
    /**
     * @var int
     */
    public $days_remaining = 0;
    /**
     * Money spent outside the bill plan
     *
     * @var null|float
     */
    public $current_spend = null;
    /**
     * Flexi units included in the price plan (null is Unlimited)
     *
     * @var null|int
     */
    public $price_plan_flexi_units = null;

    /**
     * Check whether this price plan has unlimited flexi units
     *
     * @return bool
     */
    public function is_price_plan_flexi_units_unlimited() {
        return (null == $this->price_plan_flexi_units);
    }

    /**
     * Check whether at least $min_flex_units remain
     *
     * @param   int $min_flex_units
     * @return  bool
     */
    public function is_flexi_units_remaining($min_flex_units = 1) {
        if ($this->is_price_plan_flexi_units_unlimited()) {
            return true;
        }
        if ($this->flexi_units >= $min_flex_units) {
            return true;
        }
    }

    public function proportion_of_flexi_units_remaining() {
        if ($this->is_price_plan_flexi_units_unlimited()) {
            return 1;
        }
        if (0 == $this->price_plan_flexi_units) {
            return 0;
        }
        return ($this->flexi_units / $this->price_plan_flexi_units);
    }

    public function proportion_of_days_remaining() {
        //TODO: Decide the proper number of days to use (30.4 was used as a rough representation of a month)
        return ($this->days_remaining / 30.4);
    }
}