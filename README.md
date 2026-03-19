# MyAdmin MaxMind Plugin

A MaxMind integration plugin for the MyAdmin control panel that provides fraud detection, risk scoring, and chargeback reporting capabilities using the MaxMind minFraud service.

[![Build Status](https://github.com/detain/myadmin-maxmind-plugin/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-maxmind-plugin/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-maxmind-plugin/version)](https://packagist.org/packages/detain/myadmin-maxmind-plugin)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-maxmind-plugin/downloads)](https://packagist.org/packages/detain/myadmin-maxmind-plugin)
[![License](https://poser.pugx.org/detain/myadmin-maxmind-plugin/license)](https://packagist.org/packages/detain/myadmin-maxmind-plugin)

## Features

- Real-time fraud detection via the MaxMind minFraud API for new account registrations and credit card transactions
- Configurable score thresholds for automatic account locking and credit card disabling
- Country-based and female-name-based risk score penalty adjustments
- Administrative UI for comparing MaxMind score and riskScore across accounts
- Detailed MaxMind output viewer for individual account fraud reports
- Support for both legacy minFraud (score) and modern minFraud (Insights/Factors) APIs
- Event-driven plugin architecture using Symfony EventDispatcher

## Installation

Install with Composer:

```sh
composer require detain/myadmin-maxmind-plugin
```

## Configuration

The plugin registers configurable settings through the MyAdmin settings system under "Security & Fraud" > "MaxMind Fraud Detection". Key settings include:

- **MaxMind User ID and License Key** for API authentication
- **Score and riskScore thresholds** for account locking and CC disabling
- **Female name and country penalties** with configurable cutoff limits
- **Proxy score thresholds** and no-response handling
- **Fraud reporting** and query-remaining alerts

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the LGPL-2.1 license.
