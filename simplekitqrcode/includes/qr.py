#!/usr/bin/env python3
"""Generate QR code matrix as JSON using Python qrcode library."""
import sys, json, qrcode

data = sys.argv[1] if len(sys.argv) > 1 else ''
if not data:
    print(json.dumps([[0]]))
    sys.exit(0)

qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M)
qr.add_data(data)
qr.make(fit=True)
matrix = qr.get_matrix()  # list of list of bool

# Convert bool to int (True=1=black, False=0=white)
result = [[1 if cell else 0 for cell in row] for row in matrix]
print(json.dumps(result))
