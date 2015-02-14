/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import de.quist.app.errorreporter.ExceptionReporter;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.util.Log;

public class AppMainBroadcastReceiver extends BroadcastReceiver {
	
	public AppMainBroadcastReceiver() {
		super();
		
		Log.i(Utils.TAG, Utils.getLineNumber() + ": RipRunner -> Starting up AppMainBroadcastReceiver.");
	}
	
    @Override
    public void onReceive(Context context, Intent intent) {
    	ExceptionReporter.register(context);
    	
    	Log.i(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + (intent == null ? "null" : intent));
    	
        if(intent != null && intent.getAction() != null) {
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + intent.getAction());
        	
	    	if(intent.getAction().equals(Utils.RECEIVE_CALLOUT)) {
	    		String serviceJsonString = intent.getStringExtra("callout");
	    		
	    		Intent mainAppIntent = new Intent();
	    		mainAppIntent.setAction(Utils.RECEIVE_CALLOUT_MAIN);
	    		mainAppIntent.putExtra("callout", serviceJsonString);
	            context.sendBroadcast(mainAppIntent);
	        }
	        else if(intent.getAction().equals(Utils.TRACKING_GEO)) {
	        	
	    		Intent mainAppIntent = new Intent();
	    		mainAppIntent.setAction(Utils.TRACKING_GEO_MAIN);
	            context.sendBroadcast(mainAppIntent);
	        }
	        else {
	        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got ***UNHANDLED*** intent action: " + intent.getAction());
	        }
        }
        else {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Error null intent or action.");
        }
    }
}
