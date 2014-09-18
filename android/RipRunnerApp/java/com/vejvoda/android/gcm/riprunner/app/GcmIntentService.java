/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.gcm.riprunner.app;

import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;

import org.json.JSONException;
import org.json.JSONObject;

import com.vejvoda.android.gcm.riprunner.app.R;
import com.google.android.gms.gcm.GoogleCloudMessaging;

import android.app.IntentService;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.os.SystemClock;
import android.support.v4.app.NotificationCompat;
import android.support.v4.content.LocalBroadcastManager;
import android.util.Log;

/**
 * This {@code IntentService} does the actual handling of the GCM message.
 * {@code GcmBroadcastReceiver} (a {@code WakefulBroadcastReceiver}) holds a
 * partial wake lock for this service while the service does its work. When the
 * service is finished, it calls {@code completeWakefulIntent()} to release the
 * wake lock.
 */
public class GcmIntentService extends IntentService {
    
	public static final String EXTRA_KEY_IN = "messenger";
	public static final int NOTIFICATION_ID = 1;
    private NotificationManager mNotificationManager;
    NotificationCompat.Builder builder;
    public static final String TAG = "Rip Runner";
    
    public GcmIntentService() {
        super("GcmIntentService");
    }
    
    @Override
    protected void onHandleIntent(Intent intent) {
        Bundle extras = intent.getExtras();
        GoogleCloudMessaging gcm = GoogleCloudMessaging.getInstance(this);
        // The getMessageType() intent parameter must be the intent you received
        // in your BroadcastReceiver.
        String messageType = gcm.getMessageType(intent);

        if (!extras.isEmpty()) {  // has effect of unparcelling Bundle
            /*
             * Filter messages based on message type. Since it is likely that GCM will be
             * extended in the future with new message types, just ignore any message types you're
             * not interested in, or that you don't recognize.
             */
            if (GoogleCloudMessaging.MESSAGE_TYPE_SEND_ERROR.equals(messageType)) {
                sendNotification("Send error: " + extras.toString());
            } 
            else if (GoogleCloudMessaging.MESSAGE_TYPE_DELETED.equals(messageType)) {
                sendNotification("Deleted messages on server: " + extras.toString());
                // If it's a regular GCM message, do some work.
            } 
            else if (GoogleCloudMessaging.MESSAGE_TYPE_MESSAGE.equals(messageType)) {
                Log.i(TAG, "Completed work @ " + SystemClock.elapsedRealtime());
                // Post notification of received message.
                //sendNotification("Received: " + extras.toString());
                sendNotification(getCalloutText(extras));
                updateUI(extras);
                Log.i(TAG, "Received: " + extras.toString());
            }
        }
        // Release the wake lock provided by the WakefulBroadcastReceiver.
        GcmBroadcastReceiver.completeWakefulIntent(intent);
    }

    private String getCalloutText(Bundle extras) {
    	
    	String calloutMsg = "";
    	
    	String serviceJsonString = extras.toString();
    	serviceJsonString = FireHallUtil.extractDelimitedValueFromString(
    			serviceJsonString, "Bundle\\[(.*?)\\]", 1, true);
    	try {
			JSONObject json = new JSONObject( serviceJsonString );

			if(json.has("CALLOUT_MSG")) {
				calloutMsg = URLDecoder.decode(json.getString("CALLOUT_MSG"), "utf-8");
			}
			else if(json.has("CALLOUT_RESPONSE_MSG")) {
				calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");
			}
		} 
    	catch (JSONException e) {
			//e.printStackTrace();
			throw new RuntimeException("Could not parse JSON data: " + e);
		}
    	catch (UnsupportedEncodingException e) {
			//e.printStackTrace();
			throw new RuntimeException("Could not decode JSON data: " + e);
    	}
	
    	return calloutMsg;
    }
    
    private void updateUI(Bundle extras) {
    	if (extras != null) {
        	String callout = extras.toString();
        	Intent intent = new Intent(AppMainActivity.RECEIVE_CALLOUT);
        	intent.putExtra("callout", callout);
        	LocalBroadcastManager.getInstance(this).sendBroadcast(intent);    	
    		
        }    	
    }
    
    private void sendNotification(String msg) {
        mNotificationManager = (NotificationManager)
                this.getSystemService(Context.NOTIFICATION_SERVICE);

        PendingIntent contentIntent = PendingIntent.getActivity(this, 0,
                new Intent(this, AppMainActivity.class), 0);

        NotificationCompat.Builder mBuilder =
                new NotificationCompat.Builder(this)
        	.setSmallIcon(R.drawable.ic_stat_gcm)
        	.setContentTitle("Rip Runner Notification")
        	.setStyle(new NotificationCompat.BigTextStyle()
        	.bigText(msg))
        	.setContentText(msg);

        mBuilder.setContentIntent(contentIntent);
        mNotificationManager.notify(NOTIFICATION_ID, mBuilder.build());
    }
}
