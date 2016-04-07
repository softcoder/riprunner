package com.vejvoda.android.riprunner;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;

import com.google.android.gms.iid.InstanceIDListenerService;

public class RegistrationInstanceIDListenerService extends InstanceIDListenerService {

    /**
     * Called if InstanceID token is updated. This may occur if the security of
     * the previous token had been compromised. This call is initiated by the
     * InstanceID provider.
     */
    @Override
    public void onTokenRefresh() {
        try {
            // Fetch updated Instance ID token and notify our app's server of any changes (if applicable).
            SharedPreferences sharedPreferences = getGcmPreferences();
            String senderId = sharedPreferences.getString(AppConstants.PROPERTY_SENDER_ID, "");

            // Start IntentService to register this application with GCM.
            Intent intent = new Intent(this, RegistrationIntentService.class);
            intent.putExtra(AppConstants.PROPERTY_SENDER_ID, senderId);
            startService(intent);
        }
        catch (Exception e) {
            Log.e(Utils.TAG, "Failed to handle token refresh", e);
            throw e;
        }

    }

    private SharedPreferences getGcmPreferences() {
        return getSharedPreferences(AppMainActivity.class.getSimpleName(),
                Context.MODE_PRIVATE);
    }
}