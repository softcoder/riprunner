/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.riprunner;

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

import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.PolylineOptions;

import android.os.AsyncTask;
import android.util.Log;

public class KmlLoader extends AsyncTask<String, Void, Void> {

	private KMLData data;
    private Vector<Vector<LatLng>> path_fragment;
    private Vector<PolylineOptions> path;

    public KmlLoader(KMLData data) {
        this.data = data;
    }
    
    @Override
    protected void onPreExecute() {
        super.onPreExecute();
        path_fragment = new Vector<>();
        path = new Vector <>();
     }

    @Override
    protected Void doInBackground(String... params) {
        try {
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
                String[] vett = coordText.split(" ");
                //Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document coord split count = " + vett.length);
                
                Vector<LatLng> temp = new Vector<>();
                for (String aVett : vett) {
                    temp.add(new LatLng(Double.parseDouble(aVett.split(",")[1]), Double.parseDouble(aVett.split(",")[0])));
                }
                path_fragment.add(temp);
            }
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing document fragment count = " + path_fragment.size());
        } 
        catch (IOException | ParserConfigurationException | SAXException e) {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing error!",e);
        } 

        return null;
    }

    @Override
    protected void onPostExecute(Void result) {
        super.onPostExecute(result);

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner KML file parsing adding map fragment count = " + path_fragment.size());
        for(int i=0; i < path_fragment.size(); i++){
            PolylineOptions temp = new PolylineOptions();
            for(int j=0; j< path_fragment.get(i).size(); j++) {
                temp.add(path_fragment.get(i).get(j));
            }
            path.add(temp);
        }
        this.data.setPathList(path);
    }
}
