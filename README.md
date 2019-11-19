# contao-facebook-albums
An Extension to Contao providing facebook albums as content elements

## Obtaining a user access token

The extension should automatically get the necessary tokens to read the data unless your Facebook app is *not* reviewed and published.
In other cases you may want to obtain the access token manually as follows:

1. Go to https://developers.facebook.com/tools/explorer, select your desired app, add `manage_pages` permission 
and click `Get Token > Get User Access Token` to get the short-lived access token.
2. Go to `https://graph.facebook.com/v5.0/oauth/access_token?grant_type=fb_exchange_token&client_id={app_id}&client_secret={app_secret}&fb_exchange_token={short_lived_token}` 
and copy the access_token value from the JSON response, this is the long-lived access token.
3. Use https://developers.facebook.com/tools/debug/accesstoken to verify that the token is valid, doesn't expire 
and contains the `manage_pages` scope.
4. You can then use this token `User Access Token` in the Facebook album account settings. 
