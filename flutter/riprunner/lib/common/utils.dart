import 'package:riprunner/auth/auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async';

import '../app_constants.dart';

enum APIRequestType {GET, POST}

class Utils {
  static Type typeOf<T>() => T;

  static Future<bool> hasConfigItem<T>(String keyName) async {
      final prefs = await SharedPreferences.getInstance();
      T value;
      if (T == typeOf<String>()) {
          value = prefs.getString(keyName) as T;
      } else if (T == typeOf<bool>()) {
          value = prefs.getBool(keyName) as T;
      }
      if (value == null) {
          return false;
      }
      return true;
    }

    static Future<T> getConfigItem<T>(String keyName) async {
      final prefs = await SharedPreferences.getInstance();
      T value;
      if (T == typeOf<String>()) {
          value = prefs.getString(keyName) as T;
      } else if (T == typeOf<bool>()) {
          value = prefs.getBool(keyName) as T;
      }
      return value;
    }

    static Future<void> setConfigItem<T>(String keyName, T value) async {
      final prefs = await SharedPreferences.getInstance();
      if (T == typeOf<String>()) {
          prefs.setString(keyName, value as String);
      } else if (T == typeOf<bool>()) {
          prefs.setBool(keyName, value as bool);
      }
    }

    static String addQueryParam(String url, String name, String value) {
      if (url.indexOf('?') == -1) {
        url += '?';
      }
      else {
        url += '&';
      }
      url += (name + '=' + value);
      return url;
    }

    static Future<String> injectJWTtoken(String url, bool handOffJWT) async {
      String token = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUTH);
      if(token != null && token.isNotEmpty) {
        if (url.indexOf('JWT_TOKEN') == -1) {
          url = addQueryParam(url, 'JWT_TOKEN', token);
          if (handOffJWT) {
            url = addQueryParam(url, 'JWT_TOKEN_HANDOFF', 'true');
          }
        }
      }
      return url;
    }

    static Future<void> injectJWTtokenHeader(HttpClientRequest request) async {
      String token = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUTH);
      if(token != null && token.isNotEmpty) {
        request.headers.set('jwt-token',token);
      }
    }

    static Future<void> injectSessionId(HttpClientRequest request) async {
      if(request != null) {
        Authentication.getSessionCookies().forEach((value) => request.headers.set(HttpHeaders.cookieHeader,value));
      }
    }

    static Future<String> apiRequest(String url, Map jsonMap, APIRequestType reqType, bool resetSession) async {
      try {
        HttpClient httpClient = new HttpClient();
        httpClient.badCertificateCallback = ((X509Certificate cert, String host, int port) => true);

        HttpClientRequest request;
        if(reqType == APIRequestType.GET) {
          request = await httpClient.getUrl(Uri.parse(url));
        }
        else if(reqType == APIRequestType.POST) {
          request = await httpClient.postUrl(Uri.parse(url));
        }

        if(resetSession == false) {
          await injectSessionId(request);
          await injectJWTtokenHeader(request);
        }

        if(jsonMap != null) {
          var body = json.encode(jsonMap);
          request.headers.set(HttpHeaders.contentTypeHeader, "application/json");
          request.headers.set(HttpHeaders.contentLengthHeader, body.length);
          //request.add(utf8.encode(body));
          request.write(body);
        }
        
        //String temp = request.headers.toString();
        HttpClientResponse response = await request.close();
        if(response.statusCode != 200) {
          throw Exception('API response: ${response.statusCode} message: ${response.reasonPhrase}');
        }
        
        List<String> sessionIdCookies = response.headers[HttpHeaders.setCookieHeader];
        if(sessionIdCookies != null && sessionIdCookies.isNotEmpty) {
          Authentication.setSessionCookies(sessionIdCookies.map((value) {
            int index = value.indexOf(';');
            String sessionId = (index == -1) ? value : value.substring(0, index);
            return sessionId;
          }).toList());
        }
        
        String reply = await response.transform(utf8.decoder).join();
        httpClient.close();
        reply = Uri.decodeFull(reply);
        return reply;
      }
      catch(e) {
        print(e.toString());
        throw e;
      }
    }
}