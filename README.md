# contao-facebook-albums
An Extension to Contao providing facebook albums as content elements

## Obtaining a user access token

The extension should automatically get the necessary tokens to read the data unless your Facebook app is *not* reviewed and published.

1. Go to https://developers.facebook.com/tools/explorer, select your desired app, add `manage_pages` permission 
and click `Get Token > Get User Access Token` to get the short-lived access token.
2. Go to `https://graph.facebook.com/v5.0/oauth/access_token?grant_type=fb_exchange_token&client_id={app_id}&client_secret={app_secret}&fb_exchange_token={short_lived_token}` 
and copy the access_token value from the JSON response, this is the long-lived access token.
3. Go to `https://graph.facebook.com/v5.0/me?access_token={long_lived_access_token}` to get the account ID.
4. Go to `https://graph.facebook.com/v5.0/{account_id}/accounts?access_token={long_lived_access_token}` 
and copy the `access_token` of the desired facebook page.
5. Use https://developers.facebook.com/tools/debug/accesstoken to verify that the token is valid and doesn't expire.
6. You can then use this token `Custom Access Token` in the Facebook album account settings. 
