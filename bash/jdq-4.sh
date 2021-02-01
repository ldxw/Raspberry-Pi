#!/bin/bash
echo -en '\xA0\x04\x01\xA5' > /dev/ttyUSB0; sleep 0.5; echo -en '\xA0\x04\x00\xA4' > /dev/ttyUSB0
