package com.vejvoda.android.gcm.riprunner.app;

import java.util.Vector;

import com.google.android.gms.maps.model.Polyline;
import com.google.android.gms.maps.model.PolylineOptions;

public class KMLData {

	private Vector<PolylineOptions> KMLPathList;
	private Vector<Polyline> KMLLineList;
	
    public KMLData() {
    }
    
    public Vector<PolylineOptions> getPathList() {
    	return this.KMLPathList;
    }
    public void setPathList(Vector<PolylineOptions> pathList) {
    	this.KMLPathList = pathList;
    }
    
    public Vector<Polyline> getLineList() {
    	return this.KMLLineList;
    }
    public void setLineList(Vector<Polyline> lineList) {
    	this.KMLLineList = lineList;
    }
}
