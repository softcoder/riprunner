package com.vejvoda.android.riprunner;

import android.app.IntentService;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.support.v4.content.LocalBroadcastManager;
import android.util.Log;

import com.google.android.gms.gcm.GcmPubSub;
import com.google.android.gms.gcm.GoogleCloudMessaging;
import com.google.android.gms.iid.InstanceID;

import java.io.IOException;

public class RegistrationIntentService extends IntentService {

    private static final String TAG = "RegIntentService";
    private static final String[] TOPICS = {"global"};

    public RegistrationIntentService() {
        super(TAG);
    }

    @Override
    protected void onHandleIntent(Intent intent) {
        SharedPreferences sharedPreferences = getGcmPreferences();

        try {
            // [START register_for_gcm]
            // Initially this call goes out to the network to retrieve the token, subsequent calls
            // are local.
            // R.string.gcm_defaultSenderId (the Sender ID) is typically derived from google-services.json.
            // See https://developers.google.com/cloud-messaging/android/start for details on this file.
            InstanceID instanceID = InstanceID.getInstance(this);
            if(intent != null) {
                Bundle extras = intent.getExtras();
                if (extras != null && !extras.isEmpty()) {
                    String senderId = extras.getString(AppConstants.PROPERTY_SENDER_ID);
                    String token = instanceID.getToken(senderId, GoogleCloudMessaging.INSTANCE_ID_SCOPE, null);

                    Log.i(Utils.TAG, "GCM Registration Token: " + token);
                    sharedPreferences.edit().putString(AppConstants.PROPERTY_REG_ID, token).apply();
                    sharedPreferences.edit().putBoolean(AppConstants.GOT_TOKEN_FROM_SERVER, true).apply();

                    // Implement this method to send any registration to your app's servers.
                    //sendRegistrationToServer(token);

                    // Subscribe to topic channels
                    subscribeTopics(token);

                    // You should store a boolean that indicates whether the generated token has been
                    // sent to your server. If the boolean is false, send the token to your server,
                    // otherwise your server should have already received the token.
                    //sharedPreferences.edit().putBoolean(QuickstartPreferences.SENT_TOKEN_TO_SERVER, true).apply();

                    // Notify UI that registration has completed, so the progress indicator can be hidden.
                    Intent registrationComplete = new Intent(Utils.REGISTRATION_COMPLETE);
                    LocalBroadcastManager.getInstance(this).sendBroadcast(registrationComplete);
                }
            }
        }
        catch (Exception e) {
            Log.e(Utils.TAG, "Failed to complete token refresh", e);
            // If an exception happens while fetching the new token or updating our registration data
            // on a third-party server, this ensures that we'll attempt the update at a later time.
            sharedPreferences.edit().putBoolean(AppConstants.GOT_TOKEN_FROM_SERVER, false).apply();
        }
    }

    /**
     * Persist registration to third-party servers.
     *
     * Modify this method to associate the user's GCM registration token with any server-side account
     * maintained by your application.
     *
     * @param token The new token.
     */
    //private void sendRegistrationToServer(String token) {
        // Add custom implementation, as needed.
    //}

    /**
     * Subscribe to any GCM topics of interest, as defined by the TOPICS constant.
     *
     * @param token GCM token
     * @throws IOException if unable to reach the GCM PubSub service
     */
    // [START subscribe_topics]
    private void subscribeTopics(String token) throws IOException {
        GcmPubSub pubSub = GcmPubSub.getInstance(this);
        for (String topic : TOPICS) {
            pubSub.subscribe(token, "/topics/" + topic, null);
        }
    }
    // [END subscribe_topics]

    private SharedPreferences getGcmPreferences() {
        return getSharedPreferences(AppMainActivity.class.getSimpleName(),
                Context.MODE_PRIVATE);
    }

}
