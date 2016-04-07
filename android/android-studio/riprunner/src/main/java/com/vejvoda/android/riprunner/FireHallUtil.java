/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.riprunner;

import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class FireHallUtil {

	// String callDateTime = extractDelimitedValueFromString(msgText, "Date: (.*?)$", 1, true);
    static public String extractDelimitedValueFromString(String rawValue, 
    		String regularExpression, int groupResultIndex, boolean isMultiLine) {
    	
    	String result = "";
    	if(rawValue != null && !rawValue.isEmpty()) {
	        Pattern p;
	        if(isMultiLine) {
	        	p = Pattern.compile(regularExpression,Pattern.MULTILINE);
	        }
	        else {
	        	p = Pattern.compile(regularExpression);
	        }
	        Matcher m = p.matcher(rawValue);
	        if(m.find()) {
	        	result = m.group(groupResultIndex);
	        }
    	}
    	
    	return result;
    }
	
}
