#!/bin/bash
echo 'Building Rip Runner Angular Components...'
# Angular 5
# ng build --base-href=/~softcoder/svvfd1/php/ngui/ --output-path=../php/ngui/ --aot
# Angular 6+
ng build --base-href=/~softcoder/svvfd1/php/ngui/ --output-path=../php/ngui/ --configuration=dev
