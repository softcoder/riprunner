/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import android.content.IntentFilter;

public class Utils {

    /**
     * Tag used on log messages.
     */
    static final String TAG = "RipRunner";
    static final String APK_NAME = "RipRunnerApp.apk";
	
    private static IntentFilter filter = null;
    
    // Your activity will respond to this action String
    public static final String RECEIVE_CALLOUT = "callout_data";
    public static final String TRACKING_GEO = "tracking_data";

    public static final String RECEIVE_CALLOUT_MAIN = "callout_data_main";
    public static final String TRACKING_GEO_MAIN = "tracking_data_main";
    
	public static int getLineNumber() {
		if(Thread.currentThread().getStackTrace().length > 2) {
			return Thread.currentThread().getStackTrace()[2].getLineNumber();
		}
		return -1;
	}	
	public static IntentFilter getMainAppIntentFilter() {
		if(filter == null) {
			filter = new IntentFilter();
			filter.addAction(RECEIVE_CALLOUT_MAIN);
			filter.addAction(TRACKING_GEO_MAIN);
		}
		return filter;
	}
}
