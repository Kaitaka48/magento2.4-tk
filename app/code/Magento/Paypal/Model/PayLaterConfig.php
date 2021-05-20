<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Paypal\Model;

/**
 * Provides configuration values for PayPal PayLater Banners
 */
class PayLaterConfig
{
    /**
     * Configuration key for Styles settings
     */
    const CONFIG_KEY_STYLE = 'style';

    /**
     * Configuration key for Position setting
     */
    const CONFIG_KEY_POSITION = 'position';

    /**
     * Checkout payment step placement
     */
    const CHECKOUT_PAYMENT_PLACEMENT = 'checkout_payment';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $configData = [];

    /**
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ConfigFactory $configFactory
    ) {
        $this->config = $configFactory->create();
    }

    /**
     * Check if Banner enabled for specified page
     *
     * @param string $placement
     * @return bool
     */
    public function isEnabled(string $placement): bool
    {
        $enabled = false;
        if ($this->isPPCreditEnabled()) {
            $payLaterActive = (boolean)$this->config->getPayLaterConfigValue('experience_active');
            $isPayLaterEnabled = (boolean)$this->config->getPayLaterConfigValue('enabled');
            $enabled = $payLaterActive && $isPayLaterEnabled && $this->getSectionConfig($placement, 'display');
        }
        return $enabled;
    }

    /**
     * Check that PayPal Credit enabled with any PayPal express method
     *
     * @return bool
     */
    private function isPPCreditEnabled(): bool
    {
        $isEnabled = false;
        if ($this->config->setMethod(Config::METHOD_EXPRESS)->getValue('in_context')) {
            $disabledFunding = $this->config->getValue('disable_funding_options');
            $isEnabled = $disabledFunding ? strpos($disabledFunding, 'CREDIT') === false : true;
        }

        return $isEnabled || $this->config->isMethodAvailable(Config::METHOD_WPP_BML)
            || $this->config->isMethodAvailable(Config::METHOD_WPP_PE_BML);
    }

    /**
     * Get config for a specific section and key
     *
     * @param string $section
     * @param string $key
     * @return array|string|int
     */
    public function getSectionConfig(string $section, string $key)
    {
        if (!array_key_exists($section, $this->configData)) {
            $sectionName = $section === self::CHECKOUT_PAYMENT_PLACEMENT
                ? self::CHECKOUT_PAYMENT_PLACEMENT : "${section}page";

            $this->configData[$section] = [
                'display' => (boolean)$this->config->getPayLaterConfigValue("${sectionName}_display"),
                'position' => $this->config->getPayLaterConfigValue("${sectionName}_position"),
                'style' => $this->getConfigStyles($sectionName)
            ];
        }

        return $this->configData[$section][$key];
    }

    /**
     * Get only the config styles that are needed
     *
     * @param string $sectionName
     * @return array
     */
    private function getConfigStyles(string $sectionName): array
    {
        $styleLayout = $this->config->getPayLaterConfigValue("${sectionName}_stylelayout");
        $logoType = $styleLayout === 'text'
            ? $this->config->getPayLaterConfigValue("${sectionName}_logotype") : null;
        $logoPosition = $styleLayout === 'text' && ($logoType === 'primary' || $logoType === 'alternative')
            ? $this->config->getPayLaterConfigValue("${sectionName}_logoposition") : null;
        $textColor = $styleLayout === 'text'
            ? $this->config->getPayLaterConfigValue("${sectionName}_textcolor") : null;
        $textSize = $styleLayout === 'text'
            ? $this->config->getPayLaterConfigValue("${sectionName}_textsize") : null;
        $color = $styleLayout === 'flex'
            ? $this->config->getPayLaterConfigValue("${sectionName}_color") : null;
        $ratio = $styleLayout === 'flex'
            ? $this->config->getPayLaterConfigValue("${sectionName}_ratio") : null;

        return [
            'data-pp-style-layout' => $styleLayout,
            'data-pp-style-logo-type' => $logoType,
            'data-pp-style-logo-position' => $logoPosition,
            'data-pp-style-text-color' => $textColor,
            'data-pp-style-text-size' => $textSize,
            'data-pp-style-color' => $color,
            'data-pp-style-ratio' => $ratio
        ];
    }
}
