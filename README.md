Opauth-QQ
=============
Opauth strategy for QQ authentication.

Based on Opauth's SinaWeibo Oauth2 Strategy

Getting started
----------------
0. Make sure your cake installation supports UTF8

1. Install Opauth-QQ:
   ```bash
   cd path_to_opauth/Strategy
   git clone https://github.com/fentoo/qq.git qq
   ```
2. Create QQ application at http://connect.qq.com/intro/login
   - It is a web application
	 - Callback: http://path_to_opauth/qq_callback

3. Configure QQ strategy with `key` and `secret`.

4. Direct user to `http://path_to_opauth/qq` to authenticate

Strategy configuration
----------------------

Required parameters:

```php
<?php
'QQ' => array(
	'key' => 'YOUR APP KEY',
	'secret' => 'YOUR APP SECRET'
)
```

License
---------
Opauth-QQ is MIT Licensed
