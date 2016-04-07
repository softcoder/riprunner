package com.vejvoda.android.riprunner;

import java.util.Vector;

import com.google.android.gms.maps.model.PolylineOptions;

public class KMLData {

	private Vector<PolylineOptions> KMLPathList;

    public KMLData() {
    }
    
    public Vector<PolylineOptions> getPathList() {
    	return this.KMLPathList;
    }
    public void setPathList(Vector<PolylineOptions> pathList) {
    	this.KMLPathList = pathList;
    }
}
