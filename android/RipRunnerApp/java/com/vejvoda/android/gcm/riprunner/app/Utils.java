package com.vejvoda.android.gcm.riprunner.app;

public class Utils {

    /**
     * Tag used on log messages.
     */
    static final String TAG = "RipRunner";
	
	public static int getLineNumber() {
	    return Thread.currentThread().getStackTrace()[2].getLineNumber();
	}	
}
