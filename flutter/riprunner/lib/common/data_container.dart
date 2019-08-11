
class DataContainer {
  var data;
  var dataMap = Map<String, dynamic>();

  DataContainer({var data, Map<String, dynamic> dataMap}) {
    this.data = data;
    this.dataMap = dataMap;
  }
  dynamic getData() {
    return this.data;
  }
  void setData(var data) {
    this.data = data;
  }

  dynamic getDataFromMap(String key) {
    return this.dataMap[key];
  }
  void setDataInMap(String key, var data) {
    this.dataMap[key] = data;
  }

}