package com.vejvoda.android.riprunner;

import android.util.Log;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public enum FireHallCalloutStatus {

    //Paged(0),
    //Notified(1),
    //Responding(2),
    Cancelled(3),
    //NotResponding(4),
    //Standby(5),
    //Responding_at_hall(6),
    //Responding_to_scene(7),
    //Responding_at_scene(8),
    //Responding_return_hall(9),
    Complete(10);

    private int value;

    FireHallCalloutStatus(int value) {
        this.value = value;
    }
    public int valueOf() {
        return this.value;
    }

    static public boolean isComplete(String status, String jsonStatusDef) {
        boolean result = false;
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(Integer.parseInt(status) == jsonStatus.getInt("id") &&
                        (jsonStatus.getBoolean("is_completed") || jsonStatus.getBoolean("is_cancelled"))) {
                    result = true;
                    break;
                }
            }
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
        }
        return result;

    }

    static public List<String> getResponseStatuses(String jsonStatusDef) throws JSONException {
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            List<String> statusList = new ArrayList<>();
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(jsonStatus.getBoolean("is_responding") || jsonStatus.getBoolean("is_not_responding") ||
                        jsonStatus.getBoolean("is_standby")) {
                    statusList.add(jsonStatus.getString("displayName"));
                }
            }
            return statusList;
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
            //Toast.makeText(this, "Error - parsing status JSON, msg: " + e.getMessage(), Toast.LENGTH_SHORT).show();
            throw e;
        }
    }
    static public int getDefaultResponseStatusIndex(String jsonStatusDef) throws JSONException {
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            List<String> statusList = new ArrayList<>();
            int defaultResponseIndex = 0;
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(jsonStatus.getBoolean("is_responding") || jsonStatus.getBoolean("is_not_responding") ||
                        jsonStatus.getBoolean("is_standby")) {
                    statusList.add(jsonStatus.getString("displayName"));
                    if(jsonStatus.getBoolean("is_default_response")) {
                        defaultResponseIndex = statusList.size()-1;
                    }
                }
            }
            return defaultResponseIndex;
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
            //Toast.makeText(this, "Error - parsing status JSON, msg: " + e.getMessage(), Toast.LENGTH_SHORT).show();
            throw e;
        }
    }

    static  public int getResponseStatusIdForName(String name, String jsonStatusDef) throws JSONException {
        int result = -1;
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(jsonStatus.getString("displayName").equals(name)) {
                    result = jsonStatus.getInt("id");
                    break;
                }
            }
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
            throw e;
        }
        return result;
    }
    static  public int getResponseStatusIdForCompleted(String jsonStatusDef) {
        int result = Complete.valueOf();
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(jsonStatus.getBoolean("is_completed")) {
                    result = jsonStatus.getInt("id");
                    break;
                }
            }
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
        }
        return result;
    }
    static  public int getResponseStatusIdForCancelled(String jsonStatusDef) {
        int result = Cancelled.valueOf();
        try {
            JSONArray jsonArray = new JSONArray(jsonStatusDef);
            for (int index = 0; index < jsonArray.length(); index++) {
                JSONObject jsonStatus = jsonArray.getJSONObject(index);
                if(jsonStatus.getBoolean("is_cancelled")) {
                    result = jsonStatus.getInt("id");
                    break;
                }
            }
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
        }
        return result;
    }

}
