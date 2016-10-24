import sys
import re

argnum = len(sys.argv)

if (argnum != 3):
	raise Exception("you must specify the Dockerfile and NGINX Configuration file")


Dockerfile = open(sys.argv[1], 'r')
NginxFile = open(sys.argv[2], 'r')

DockerContent = Dockerfile.read()
NginxContent = NginxFile.read()

Dockerfile.close()
NginxFile.close()

DockerLines = DockerContent.split("\n")
NginxLines = NginxContent.split("\n");

regexForEnvironment = re.compile('^\s*ENV\s+([\w_]+)\s+([\w_]+)')
regexForOutput = re.compile('include fastcgi_params;')

matchArray = []

for line in DockerLines:
    match = regexForEnvironment.match(line)
    if match:
    	fixedMatchString = "fastcgi_param " + match.group(1) + ' "' + match.group(2) + '";';
    	matchArray.append(fixedMatchString)


joinedFixedMatches = "\n".join(matchArray)

NginxLineArray = []

for line in NginxLines:
    match = regexForOutput.search(line)
    if match:
    	NginxLineArray.append(line + '\n'+ joinedFixedMatches)
    else:
    	NginxLineArray.append(line)

NginxFile = open(sys.argv[2], 'w')

NginxFile.write('\n'.join(NginxLineArray))