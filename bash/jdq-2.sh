#!/bin/bash
echo -en '\xA0\x02\x01\xA3' > /dev/ttyUSB0; sleep 0.5; echo -en '\xA0\x02\x00\xA2' > /dev/ttyUSB0
