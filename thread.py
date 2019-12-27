# coding=utf-8
import threading
import requests
import Queue
import sys
import re
def Threads():
    threadlist=[]
    queue = Queue.Queue()
    for ip in open('ip txt','r'):#扫描好的ip
        queue.put(ip.replace('\n',''))
    for x in range(0,10):#线程数
        th=threading.Thread(target=scan_Hikvision,args=(queue,''))
	    threadlist.append(th)
    for t in threadlist:
        t.start()
    for t in threadlist:
        t.join()
    def.scan_Hikvision(queue):
        while not queue.empty():
	    ip = queue.get()
	        try:
		    print "[*]scan:" + ip
		    r = requests.get(url=("http://%s/PSIA/System/deviceinfo" % ip),auth=('admin','123456')),
     timeout=10)#增加了timeout超时
                    if r.status_code==200:
		        deviceName = re.findall(r'<deviceName>(.+?)</deviceName>',r.text)[0]
			f = open('ok.txt','a+')
			f.write(("ip:%s deviceName:%s\n" % (ip,deviceName)))
			f.clode()
			break
	        except:
		     continue
if __name__ == '__main__':
    print "Running scan_Hikvision ......"
    Threads()