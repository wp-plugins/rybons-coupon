<?php

include_once('RybonsCoupon_LifeCycle.php');

class RybonsCoupon_Plugin extends RybonsCoupon_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'rybonsCouponMerchantKey' => array(__('Merchant Key', 'rybonscoupon-plugin')),
            'rybonsCouponConnectionMode' => array(__('Connection Mode', 'rybonscoupon-plugin'), 'DEV', 'LIVE'),
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'RybonsCoupon';
    }

    protected function getMainPluginFileName() {
        return 'rybonscoupon.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }

    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
        
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }
        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37
        add_filter('woocommerce_checkout_coupon_message', array(&$this, 'woocommerce_rename_coupon_message_on_checkout'));
        add_action('woocommerce_get_shop_coupon_data', array(&$this, 'rybonscoupon_process_valid_coupon'));
        add_action('woocommerce_after_cart_contents', array(&$this, 'rybonscoupon_after_cart_table'));
        add_action('woocommerce_cart_calculate_fees', array(&$this, 'rybonscoupon_add_cart_fee'));

        //add_action('woocommerce_after_checkout_validation', array(&$this, 'rybonscoupon_checkout_validation_save_billing_information'));
        add_action('woocommerce_after_checkout_validation', array(&$this, 'rybonscoupon_checkout_validation'));
        add_action('woocommerce_checkout_order_processed', array(&$this, 'rybonscoupon_checkout_order_processed'));


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39
        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41
    }

    // rename the "Have a Coupon?" message on the checkout page
    public function woocommerce_rename_coupon_message_on_checkout() {
        return "Don't have a Rybons Coupon? Head over to <a href='http://www.rybons.com.ng' target='_blank'>Rybons.com.ng</a> and get one FREE!";
    }

    // rename the coupon field on the checkout page
    public function woocommerce_rename_coupon_field_on_checkout($translated_text, $text, $text_domain) {

        // bail if not modifying frontend woocommerce text
        if (is_admin() || 'woocommerce' !== $text_domain) {
            return $translated_text;
        }

        if ('Coupon code' === $text) {
            $translated_text = 'Apply a coupon or one from Rybons Coupon';
        } elseif ('Apply Coupon' === $text) {
            $translated_text = 'Apply Coupon or one from Rybons Coupon ';
        }

        return $translated_text;
    }

    public function rybonscoupon_after_cart_table() {
        global $woocommerce;
        echo '<div class="submit">';

        if (!$this->RybonsCoupon_getFromSession("rybonsCouponCode")) {
            echo '
              <table cellspacing="0"><tbody><tr><td colspan="6" class="actions">
              <div class="coupon">
              <label style="display:block" for="coupon_code">Rybons Coupon</label> 
              <input type="text"  name="rybonscoupon_code" class="input-text"  value="" placeholder="Rybons Coupon code">
               <input type="submit" class="button" name="rybonsCouponButton" value="Apply Coupon"></div></td></tr></tbody><table>';
        } else {
            echo '
              <table cellspacing="0"><tbody><tr><td colspan="6" class="actions">
              <div class="coupon">
              <label style="display:block" for="coupon_code">Rybons Coupon</label> 
              
              <input type="text" style="width:50%" name="rybonscoupon_code" readonly  value="' . $this->RybonsCoupon_getFromSession("rybonsCouponCode") . '">
              <input type="submit" class="button" name="removeRybonsCouponButton" value="Remove"/></div></td></tr></tbody><table>';
        }
        echo '</div>';
    }

    public function rybonscoupon_add_cart_fee() {
        global $woocommerce;
        $postData = $_POST;
        if (isset($postData['rybonsCouponButton'])) {
            $merchantKey = $this->getOption('rybonsCouponMerchantKey');
            $mode = $this->getOption('rybonsCouponConnectionMode');
            $coupon_code = $postData['rybonscoupon_code'];

            $url = "http://rybons.com.ng/webservice/coupons/get.json";
            if ($mode == "LIVE") {
                $url = "http://rybons.com.ng/webservice/coupons/get.json";
            }

            $postFields = "?coupon_code={$coupon_code}&merchant_key={$merchantKey}";
            $url .= $postFields;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'RYBONS WooCommerce Extension'
            ));
            $result = curl_exec($curl);
            curl_close($curl);

            if ($result != false) {
                $response = json_decode($result, true);
                if (isset($response["PayLoad"]["status"]) && $response["PayLoad"]["status"]) {
                    $couponResponse = $response["PayLoad"]["data"]["Coupon"];
                    $error = "";
                    $couponValue = $this->getRybonsCouponValue($couponResponse, $error);
                    if ($couponValue > 0) {
                        $this->RybonsCoupon_SaveToSession("use_rybonsCoupon", true);
                        $this->RybonsCoupon_SaveToSession("rybonsCoupon_amt", $couponValue);
                        $this->RybonsCoupon_SaveToSession("rybonsCouponCode", $coupon_code);
                        $woocommerce->cart->discount_total = $couponValue;
                        wc_add_notice('The Rybons Coupon has been applied.', 'success');
                    } else {
                        wc_add_notice($error, 'error');
                    }
                } else {
                    wc_add_notice('Invalid coupon. Coupon may have expired or has reached maximum limit.', 'error');
                }
            }
        }

        if (isset($postData['removeRybonsCouponButton'])) {
            $this->RybonsCoupon_destroySession();
        }

        if ($this->RybonsCoupon_getFromSession("rybonsCoupon_amt")) {
            $woocommerce->cart->add_fee(__('Rybons Coupon(' . $this->RybonsCoupon_getFromSession("rybonsCouponCode") . ')', 'woocommerce'), -$this->RybonsCoupon_getFromSession("rybonsCoupon_amt"));
        }
    }

    public function rybonscoupon_checkout_validation_save_billing_information($posted) {
        global $woocommerce;
        $value = json_encode(array(
            "fname" => $posted['billing_first_name'],
            "lname" => $posted['billing_last_name'],
            "email" => $posted['billing_email'],
            "phone" => $posted['billing_phone'],
        ));
    }

    public function rybonscoupon_checkout_validation($posted) {
        global $woocommerce;
        if ($this->RybonsCoupon_getFromSession("rybonsCouponCode")) {
            $merchantKey = $this->getOption('rybonsCouponMerchantKey');
            $mode = $this->getOption('rybonsCouponConnectionMode');
            $coupon_code = $this->RybonsCoupon_getFromSession("rybonsCouponCode");

            $url = "http://rybons.com.ng/webservice/coupons/check.json";
            if ($mode == "LIVE") {
                $url = "http://rybons.com.ng/webservice/coupons/check.json";
            }

            $postFields = "?coupon_code={$coupon_code}&merchant_key={$merchantKey}";
            $url .= $postFields;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'RYBONS WooCommerce Extension'
            ));
            $result = curl_exec($curl);
            curl_close($curl);

            if ($result != false) {
                $response = json_decode($result, true);
                if (isset($response["PayLoad"]["status"]) && $response["PayLoad"]["status"]) {
                    $value = json_encode(array(
                        "fname" => $posted['billing_first_name'],
                        "lname" => $posted['billing_last_name'],
                        "email" => $posted['billing_email'],
                        "phone" => $posted['billing_phone'],
                    ));
                    $this->RybonsCoupon_SaveToSession("rybonsCouponCustomer", $value);
                } else {
                    $woocommerce->add_error($response["PayLoad"]["status"]["error"], 'woocommerce');
                }
            } else {
                $woocommerce->add_error("Failed to connect to Rybons Server", 'woocommerce');
            }
        }
    }

    public function rybonscoupon_checkout_order_processed($order_id, $posted) {
        global $woocommerce;
        if ($this->RybonsCoupon_getFromSession("rybonsCouponCode")) {
            $merchantKey = $this->getOption('rybonsCouponMerchantKey');
            $mode = $this->getOption('rybonsCouponConnectionMode');
            $code = $this->RybonsCoupon_getFromSession("rybonsCouponCode");

            $details = array();
            $details["customer"] = json_decode($this->RybonsCoupon_getFromSession("rybonsCouponCustomer"), true);

            $details['order_id'] = $order_id;
            $details['purchase_amount'] = $woocommerce->cart->cart_contents_total;

            $url = "http://rybons.com.ng/webservice/coupons/apply.json";
            if ($mode == "LIVE") {
                $url = "http://rybons.com.ng/webservice/coupons/apply.json";
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'RYBONS WooCommerce Extension',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => array(
                    "coupon_code" => $code,
                    "merchant_key" => $merchantKey,
                    "buyer_details" => json_encode($details)
                )
            ));
            $result = curl_exec($curl);
            curl_close($curl);

            if ($result != false) {
                $response = json_decode($result, true);
                if (isset($response["PayLoad"]["status"]) && $response["PayLoad"]["status"]) {
                    //$this->RybonsCoupon_destroySession();
                } else {
                    $woocommerce->add_error($response["PayLoad"]["status"]["error"], 'woocommerce');
                }
            } else {
                $woocommerce->add_error("Failed to connect to Rybons Server", 'woocommerce');
            }
        }
    }

    private function getRybonsCouponValue($couponResponse, &$error = null) {
        global $woocommerce;
        if ($woocommerce->cart->cart_contents_total > $couponResponse['minimum_purchase_value']) {
            return $couponResponse["percentage_coupon_value"] > 0 ? (($couponResponse["percentage_coupon_value"] / 100) * $woocommerce->cart->cart_contents_total) : $couponResponse['flat_coupon_value'];
        } else {
            $error = "A minimum purchase of N {$couponResponse['minimum_purchase_value']} is required.";
        }
        return 0.00;
    }

    private function parseRybonsCouponData($couponResponse) {
        return array(
            'coupon_id' => $couponResponse['id'],
            'code' => $couponResponse['coupon_code'],
            'name' => $couponResponse['title'],
            'type' => $couponResponse["percentage_coupon_value"] > 0 ? "P" : "F",
            'discount' => $couponResponse["percentage_coupon_value"] > 0 ? $couponResponse["percentage_coupon_value"] : $couponResponse['flat_coupon_value'],
            'shipping' => 0,
            'total' => $couponResponse['minimum_purchase_value'],
            'product' => array(),
            'date_start' => $couponResponse['created'],
            'date_end' => date("Y-m-d", $couponResponse['expires_on']),
            'uses_total' => $couponResponse['quantity'],
            'uses_customer' => $couponResponse['quantity'],
            'status' => 1,
            'date_added' => date("Y-m-d")
        );
    }

    private function RybonsCoupon_SaveToSession($key, $value) {
        if (!session_id()) {
            session_start();
        }
        $_SESSION["RYBONSCOUPON_PLUGIN"][$key] = $value;
    }

    private function RybonsCoupon_getFromSession($key) {
        if (!session_id()) {
            session_start();
        }
        return $_SESSION["RYBONSCOUPON_PLUGIN"][$key];
    }

    private function RybonsCoupon_destroySession() {
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION["RYBONSCOUPON_PLUGIN"]);
    }

}