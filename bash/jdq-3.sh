#!/bin/bash
echo -en '\xA0\x03\x01\xA4' > /dev/ttyUSB0; sleep 0.5; echo -en '\xA0\x03\x00\xA3' > /dev/ttyUSB0
