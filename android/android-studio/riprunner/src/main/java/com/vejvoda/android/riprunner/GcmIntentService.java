/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.riprunner;

import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;

import org.json.JSONException;
import org.json.JSONObject;

import com.google.android.gms.gcm.GcmListenerService;

import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.os.SystemClock;
import android.support.v4.app.NotificationCompat;
import android.support.v4.content.LocalBroadcastManager;
import android.util.Log;

public class GcmIntentService extends GcmListenerService {
    
	public static final int NOTIFICATION_ID = 1;
    public GcmIntentService() {
		super();
    }

/*
    @Override
    protected void onHandleIntent(Intent intent) {
        try {
            Bundle extras = intent.getExtras();
            GoogleCloudMessaging gcm = GoogleCloudMessaging.getInstance(this);
            // The getMessageType() intent parameter must be the intent you received
            // in your BroadcastReceiver.
            String messageType = gcm.getMessageType(intent);

            if (extras != null && !extras.isEmpty() &&
                    messageType != null && !messageType.isEmpty()) {  // has effect of unparcelling Bundle
                //
                // Filter messages based on message type. Since it is likely that GCM will be
                // extended in the future with new message types, just ignore any message types you're
                // not interested in, or that you don't recognize.
                //
                if (GoogleCloudMessaging.MESSAGE_TYPE_SEND_ERROR.equals(messageType)) {
                    sendNotification("Send error: " + extras.toString());
                } else if (GoogleCloudMessaging.MESSAGE_TYPE_DELETED.equals(messageType)) {
                    sendNotification("Deleted messages on server: " + extras.toString());
                    // If it's a regular GCM message, do some work.
                } else if (GoogleCloudMessaging.MESSAGE_TYPE_MESSAGE.equals(messageType)) {
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": Completed work @ " + SystemClock.elapsedRealtime());
                    // Post notification of received message.
                    //sendNotification("Received: " + extras.toString());
                    sendNotification(getCalloutText(extras));
                    updateUI(extras);
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": Received: " + extras.toString());
                }
            }
            // Release the wake lock provided by the WakefulBroadcastReceiver.
            GcmBroadcastReceiver.completeWakefulIntent(intent);
        }
        catch (Exception e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Error ", e);
            throw e;
        }
    }
*/

    /**
     * Called when message is received.
     *
     * @param from SenderID of the sender.
     * @param data Data bundle containing message data as key/value pairs.
     *             For Set of keys use data.keySet().
     */
    // [START receive_message]
    @Override
    public void onMessageReceived(String from, Bundle data) {
        //String message = data.getString("message");
        Log.i(Utils.TAG, "From: " + from);
        //Log.i(Utils.TAG, "Message: " + message);

        //if (from.startsWith("/topics/")) {
            // message received from some topic.
        //} else {
            // normal downstream message.
        //}


        try {
            if (!data.isEmpty()) {  // has effect of unparcelling Bundle
                Log.i(Utils.TAG, Utils.getLineNumber() + ": Completed work @ " + SystemClock.elapsedRealtime());
                // Post notification of received message.
                //sendNotification("Received: " + extras.toString());
                sendNotification(getCalloutText(data));
                updateUI(data);
                Log.i(Utils.TAG, Utils.getLineNumber() + ": Received: " + data.toString());
            }
        }
        catch (Exception e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Error ", e);
            throw e;
        }

        // [START_EXCLUDE]
        /**
         * Production applications would usually process the message here.
         * Eg: - Syncing with server.
         *     - Store message in local database.
         *     - Update UI.
         */

        /**
         * In some cases it may be useful to show a notification indicating to the user
         * that a message was received.
         */
        //sendNotification(message);
        // [END_EXCLUDE]
    }
    // [END receive_message]

    private String getCalloutText(Bundle extras) {
    	String calloutMsg = "";
    	
    	try {
        	String serviceJsonString = extras.toString();
        	serviceJsonString = FireHallUtil.extractDelimitedValueFromString(
        			serviceJsonString, "Bundle\\[(.*?)\\]", 1, true);
    		
			JSONObject json = new JSONObject( serviceJsonString );

			if(json.has("DEVICE_MSG")) {
				calloutMsg = URLDecoder.decode(json.getString("device-status"), "utf-8");
			}
			else if(json.has("CALLOUT_MSG")) {
				calloutMsg = URLDecoder.decode(json.getString("CALLOUT_MSG"), "utf-8");
			}
			else if(json.has("CALLOUT_RESPONSE_MSG")) {
				calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");
			}
		} 
    	catch (JSONException e) {
    		Log.e(Utils.TAG, Utils.getLineNumber() + ": Error ", e);
			throw new RuntimeException("Could not parse JSON data: " + e);
		}
    	catch (UnsupportedEncodingException e) {
    		Log.e(Utils.TAG, Utils.getLineNumber() + ": Error ", e);
			throw new RuntimeException("Could not decode JSON data: " + e);
    	}

    	return calloutMsg;
    }
    
    private void updateUI(Bundle extras) {
    	if (extras != null) {
        	String callout = extras.toString();
        	Intent intent = new Intent(Utils.RECEIVE_CALLOUT);
        	intent.putExtra("callout", callout);
        	
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner sending broadcast intent for callout: " + callout);
        	
        	boolean result = LocalBroadcastManager.getInstance(this).sendBroadcast(intent);
        	
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner sent broadcast intent got result: " + result);
        }    	
    }
    
    private void sendNotification(String msg) {
		NotificationManager mNotificationManager = (NotificationManager)
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
