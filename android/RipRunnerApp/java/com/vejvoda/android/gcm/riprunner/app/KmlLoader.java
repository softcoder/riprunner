/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import java.io.FileNotFoundException;
import java.io.IOException;
import java.net.URL;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

//import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.Polyline;
import com.google.android.gms.maps.model.PolylineOptions;

import android.graphics.Color;
import android.os.AsyncTask;
import android.util.Log;

public class KmlLoader extends AsyncTask<String, Void, Void> {

	//GoogleMap googleMap;
	KMLData data;
    Vector<Vector<LatLng>> path_fragment;
    
    Vector<PolylineOptions> path;
    //Vector<Polyline> lines;

    public KmlLoader(KMLData data) {
    	//this.googleMap = googleMap;
    	this.data = data;
    }
    
    @Override
    protected void onPreExecute() {
        super.onPreExecute();
        path_fragment = new Vector<Vector<LatLng>>();
        path = new Vector <PolylineOptions>();
        //lines = new Vector<Polyline>();
     }

    @Override
    protected Void doInBackground(String... params) {
        try {
            //InputStream inputStream = new FileInputStream(params[0]);
        	String xmlUrl = params[0];
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner about to try loading KML file: [" + xmlUrl + "]");
                
        	URL url = new URL(xmlUrl);
        
            DocumentBuilder docBuilder =  DocumentBuilderFactory.newInstance().newDocumentBuilder();
            Document document = docBuilder.parse(new InputSource(url.openStream()));
            if (document == null) {
            	Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document = null!");
                return null;
            }

            NodeList listCoordinateTag = document.getElementsByTagName("coordinates");
            
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document coords count = " + listCoordinateTag.getLength());
            
            for (int i = 0; i < listCoordinateTag.getLength(); i++) {

                String coordText = listCoordinateTag.item(i).getFirstChild().getNodeValue().trim();
                String[] vett = coordText.split("\\ ");
                //Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document coord split count = " + vett.length);
                
                Vector<LatLng> temp = new Vector<LatLng>();
                for(int j=0; j < vett.length; j++){
                    //temp.add(new LatLng(Double.parseDouble(vett[j].split("\\,")[0]),Double.parseDouble(vett[j].split("\\,")[1])));
                	temp.add(new LatLng(Double.parseDouble(vett[j].split("\\,")[1]),Double.parseDouble(vett[j].split("\\,")[0])));
                }
                path_fragment.add(temp);
            }
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document fragment count = " + path_fragment.size());
        } 
        catch (FileNotFoundException e) {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing error!",e);
        } 
        catch (ParserConfigurationException e) {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing error!",e);
        } 
        catch (SAXException e) {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing error!",e);
        } 
        catch (IOException e) {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing error!",e);
        }

        return null;
    }

    @Override
    protected void onPostExecute(Void result) {
        super.onPostExecute(result);

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing adding map fragment count = " + path_fragment.size());
        //googleMap.clear();
        for(int i=0; i < path_fragment.size(); i++){

            // Poliline options
            PolylineOptions temp = new PolylineOptions();

            for(int j=0; j< path_fragment.get(i).size(); j++)
            temp.add(path_fragment.get(i).get(j));

            path.add(temp);
        }

        //for(int i = 0; i < path.size(); i++)
            //lines.add(googleMap.addPolyline(path.get(i)));
        	

//        for(int i = 0; i < lines.size(); i++){
//           lines.get(i).setWidth(4);
//           lines.get(i).setColor(Color.RED);
//           lines.get(i).setGeodesic(true);
//           lines.get(i).setVisible(true);
//        }
        
        //this.data.setLineList(lines);
        this.data.setPathList(path);
    }
}	
