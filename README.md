# PayPal Payment

## Maintainer Contacts
---------------------
*  Ryan Dao
*  Frank Mullenger
*  Jeremy Shipman

## Requirements
---------------------
* SilverStripe 3.0
* SilverStripe Payment

## Documentation
---------------------
### Usage Overview

This module provides PayPal payment support for the SilverStripe Payment module. 

### Installation guide
  Add to mysite/_config:
    
    PayPalGateway: 
      dev: // to be added only if Sandbox is used
        url: 
          'https://api-3t.sandbox.paypal.com/nvp'
        authentication:
          username:
          password:
          signature: 
      live:
        url: 
          'https://api-3t.paypal.com/nvp'
        authentication:
          username:
          password:
          signature: 

To get PayPal Sandbox test accounts, follow the [PayPal documentation](https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_Sandbox_UserGuide.pdf). 