import requests
import re
urls = "http://ip/PSIA/System/deviceinfo"
r = requests.get(url=urls,auth=('admin','123456'))
deviceName = re.findall(r'<deviceName>(.+?)</deviceName>',r.text)[0]