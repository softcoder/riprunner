/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

public class Utils {

    /**
     * Tag used on log messages.
     */
    static final String TAG = "RipRunner";
    static final String APK_NAME = "RipRunnerApp.apk";
	
	public static int getLineNumber() {
		if(Thread.currentThread().getStackTrace().length > 2) {
			return Thread.currentThread().getStackTrace()[2].getLineNumber();
		}
		return -1;
	}	
}
