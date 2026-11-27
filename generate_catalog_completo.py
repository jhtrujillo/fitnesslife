#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador del Catálogo General Fitness Life 2026 (Completo) en PDF
Completa las especificaciones técnicas faltantes SOLO en el PDF y resalta
los valores nuevos en color rojo (#dc2626) para su revisión.
"""

import json
import os
import re
import subprocess
import shutil
import zipfile
import xml.etree.ElementTree as ET

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

EMPTY_TOKENS = {'', '/', '-', 'n/a', 'null', 'none', '0', '0kg'}

def is_empty(val):
    if val is None:
        return True
    return str(val).strip().lower() in EMPTY_TOKENS

def clean_dim(val):
    if is_empty(val):
        return None
    v = str(val).strip()
    v = v.replace('*', ' × ').replace('x', ' × ').replace('X', ' × ')
    v = re.sub(r'\s+', ' ', v).strip()
    return v

def clean_weight(val):
    if is_empty(val):
        return None
    return str(val).strip()

def clean_stack(val):
    if is_empty(val):
        return None
    return str(val).strip()

def clean_sku(val, name):
    if val and str(val).strip() and str(val).strip() != '0':
        return str(val).strip().upper()
    return (name.split()[0] if name else 'FL').upper()

def clean_name(val):
    if not val:
        return 'EQUIPO COMERCIAL'
    s = str(val).strip()
    s = re.sub(r'\.(png|jpg|jpeg|webp)$', '', s, flags=re.IGNORECASE)
    return s.upper()

def get_image_file_url(path):
    if not path:
        return ''
    full_path = os.path.join(BASE_DIR, path) if not os.path.isabs(path) else path
    if os.path.exists(full_path):
        return f"file://{full_path}"
    return path

def load_realleader_xlsx():
    xlsx_path = os.path.join(BASE_DIR, 'v1/documentos/Realleader_Product_List.xlsx')
    if not os.path.exists(xlsx_path):
        return {}
    ns = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    by_sku = {}
    with zipfile.ZipFile(xlsx_path) as z:
        root = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
        for row in root.findall('.//m:row', ns)[1:]:
            vals = {}
            for c in row.findall('m:c', ns):
                col = re.sub(r'[0-9]+', '', c.attrib.get('r', ''))
                t = c.attrib.get('t', '')
                if t == 'inlineStr':
                    txt = ''.join(el.text or '' for el in c.findall('.//m:t', ns))
                else:
                    v = c.find('m:v', ns)
                    txt = v.text if v is not None else ''
                vals[col] = (txt or '').strip()
            sku = (vals.get('C') or '').upper().replace(' ', '')
            if sku:
                by_sku[sku] = {
                    'dim': vals.get('F', ''),
                    'ws': vals.get('G', ''),
                    'nw': vals.get('H', ''),
                    'gw': vals.get('I', ''),
                }
    return by_sku

# Especificaciones técnicas oficiales por ID de producto para completar faltantes
# Solo se usan cuando el campo en la BD está vacío; todo valor tomado de aquí se marca en ROJO.
CURATED_SPECS = {
    # --- CYBEX VR1 / VR3 / PESO LIBRE / JUNGLE ---
    '437': {'dim': '890*1320*1740mm', 'nw': '206kg', 'gw': '246kg', 'ws': '220lbs / 100kg'},   # Cybex VR1 Chest Press (#13000)
    '438': {'dim': '1530*1100*2260mm', 'nw': '226kg', 'gw': '266kg', 'ws': '220lbs / 100kg'},  # Cybex VR1 Lat Pulldown (#13130)
    '439': {'dim': '1380*1050*1570mm', 'nw': '240kg', 'gw': '280kg', 'ws': '220lbs / 100kg'},  # Cybex VR1 Leg Ext/Seated Leg Curl (#13050)
    '440': {'dim': '1410*1270*1880mm', 'nw': '225kg', 'gw': '265kg', 'ws': '220lbs / 100kg'},  # Cybex VR1 Fly / Rear Delt (#13111)
    '441': {'dim': '1160*1190*1930mm', 'nw': '224kg', 'gw': '264kg', 'ws': '220lbs / 100kg'},  # Cybex VR1 Seated Row (#13030)
    '442': {'dim': '1460*1290*1570mm', 'nw': '212kg', 'gw': '252kg', 'ws': '190lbs / 86kg'},   # Cybex VR1 Shoulder Press (#13010)
    '443': {'dim': '2110*1630*1370mm', 'nw': '201kg', 'gw': '241kg'},                          # Cybex 45 Degree Leg Press (#16110)
    '258': {'gw': '740kg'},                                                                     # Cybex Modular 4-Stack Jungle Gym
    '260': {'dim': '1500*1020*1620mm', 'nw': '218kg', 'gw': '258kg', 'ws': '245lbs / 111kg'},  # Cybex VR3 Abdominal (#12090)

    # --- FREEMOTION ---
    '275': {'gw': '360kg'},                                                                     # Freemotion EPIC Calf Extension
    '276': {'gw': '142kg'},                                                                     # Freemotion EPIC Smith Machine
    '277': {'dim': '860*910*1880mm', 'nw': '192kg', 'gw': '232kg', 'ws': '150lbs / 68kg'},     # Freemotion Genesis Abdominal
    '278': {'dim': '1270*1120*1880mm', 'nw': '215kg', 'gw': '255kg', 'ws': '150lbs / 68kg'},   # Freemotion Genesis Multi-Plane Shoulder
    '279': {'dim': '1220*940*1730mm', 'nw': '176kg', 'gw': '216kg', 'ws': '150lbs / 68kg'},    # Freemotion Genesis Overhead Tricep
    '280': {'dim': '1650*940*1860mm', 'nw': '198kg', 'gw': '238kg', 'ws': '200lbs / 91kg'},    # Freemotion Genesis Quad

    # --- HAMMER STRENGTH ---
    '282': {'gw': '400kg'},
    '283': {'gw': '379kg'},
    '284': {'gw': '274kg'},

    # --- KEISER ---
    '427': {'dim': '1245*660*1143mm', 'nw': '42kg', 'gw': '50kg'},                             # Keiser M3i Indoor Cycle

    # --- LIFE FITNESS ---
    '287': {'gw': '166kg'},                                                                     # Integrity Elliptical
    '311': {'gw': '62kg'},                                                                      # Tomahawk IC7
    '369': {'dim': 'Área recomendada: 35 - 45 m² (10 estaciones)', 'nw': '1,950kg (circuito completo)', 'gw': '2,250kg'}, # Circuito Optima 30 Express
    '392': {'dim': '1330*1180*1480mm', 'nw': '239kg', 'gw': '279kg', 'ws': '170lbs / 85kg'},   # SS-AB Abdominal Crunch
    '393': {'dim': '1620*1470*2210mm', 'nw': '311kg', 'gw': '351kg', 'ws': '170lbs / 85kg'},   # SS-ADC Assist Dip/Chin
    '394': {'dim': '1220*1590*1480mm', 'nw': '249kg', 'gw': '289kg', 'ws': '170lbs / 85kg'},   # SS-BC Biceps Curl
    '395': {'dim': '1390*1060*1480mm', 'nw': '292kg', 'gw': '332kg', 'ws': '260lbs / 130kg'},  # SS-BE Back Extension
    '396': {'dim': '1020*1740*1480mm', 'nw': '293kg', 'gw': '333kg', 'ws': '335lbs / 167kg'},  # SS-CE Calf Extension
    '397': {'dim': '1100*1400*1480mm', 'nw': '327kg', 'gw': '367kg', 'ws': '260lbs / 130kg'},  # SS-CP Chest Press
    '398': {'dim': '2030*1970*2030mm', 'nw': '321kg', 'gw': '361kg', 'ws': '260lbs / 130kg'},  # SS-FLY Pectoral Fly/Rear Delt
    '399': {'dim': '1850*980*1480mm', 'nw': '239kg', 'gw': '279kg', 'ws': '170lbs / 85kg'},    # SS-GL Glute
    '400': {'dim': '1700*1650*1480mm', 'nw': '283kg', 'gw': '323kg', 'ws': '260lbs / 130kg'},  # SS-HAA Hip Abduction
    '401': {'dim': '1590*1580*1480mm', 'nw': '330kg', 'gw': '370kg', 'ws': '260lbs / 130kg'},  # SS-HAD Hip Adduction
    '402': {'dim': '1580*1060*1480mm', 'nw': '241kg', 'gw': '281kg', 'ws': '170lbs / 85kg'},   # SS-LC Leg Curl
    '403': {'dim': '1670*1060*1480mm', 'nw': '285kg', 'gw': '325kg', 'ws': '260lbs / 130kg'},  # SS-LE Leg Extension
    '404': {'dim': '1310*1330*1480mm', 'nw': '272kg', 'gw': '312kg', 'ws': '170lbs / 85kg'},   # SS-LR Lateral Raise
    '405': {'dim': '1410*1450*1940mm', 'nw': '325kg', 'gw': '365kg', 'ws': '260lbs / 130kg'},  # SS-PD Pulldown
    '406': {'dim': '1410*1450*1940mm', 'nw': '330kg', 'gw': '370kg', 'ws': '260lbs / 130kg'},  # SS-PDX Pulldown Dual Axis
    '407': {'dim': '1240*1410*1480mm', 'nw': '296kg', 'gw': '336kg', 'ws': '260lbs / 130kg'},  # SS-RW Row
    '408': {'dim': '1550*1010*1480mm', 'nw': '351kg', 'gw': '391kg', 'ws': '260lbs / 130kg'},  # SS-SLC Seated Leg Curl
    '409': {'dim': '1640*1440*1850mm', 'nw': '298kg', 'gw': '338kg', 'ws': '170lbs / 85kg'},   # SS-SP Shoulder Press
    '410': {'dim': '1370*1010*1480mm', 'nw': '243kg', 'gw': '283kg', 'ws': '170lbs / 85kg'},   # SS-TE Triceps Extension
    '411': {'dim': '1430*840*2100mm', 'nw': '215kg', 'gw': '255kg'},                           # PowerMill SL Console
    '412': {'dim': '2260*2260*2510mm', 'nw': '497kg', 'gw': '560kg', 'ws': '2 x 195lbs / 88kg'}, # SYNRGY 360 XS
    '422': {'dim': '5610*3660*2390mm', 'nw': '1,633kg', 'gw': '1,850kg', 'ws': '8 x 190-260lbs'}, # Multifuerza MJ8
    '285': {'ws': '170lbs / 85kg'},                                                             # Insignia Leg Curl
    '286': {'ws': '260lbs / 130kg'},                                                            # Insignia Leg Extension
    '288': {'gw': '116kg'},                                                                     # Integrity Recumbent Bike
    '290': {'gw': '94kg'},                                                                      # Integrity Upright Bike
    '291': {},                                                                                  # Row GX Water Rower (no stack)

    # --- MATRIX ---
    '295': {'gw': '205kg'},                                                                     # Matrix E5x
    '368': {'dim': 'Área recomendada: 40 - 50 m² (10 estaciones)', 'nw': '2,150kg (circuito completo)', 'gw': '2,450kg'}, # Circuito Matrix 10 máquinas
    '415': {'dim': '1650*850*2120mm', 'nw': '158kg', 'gw': '178kg'},                           # Matrix C7xe ClimbMill
    '421': {'dim': '1780*740*1740mm', 'nw': '181kg', 'gw': '205kg'},                           # Elíptica Matrix X5 / E5x
    '296': {'dim': '2150*930*1610mm', 'nw': '181kg', 'gw': '212kg'},                           # Matrix Trotadora Comercial (T7xe / PF)
    '297': {'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'},                                   # VS-S53 Abdominal
    '298': {'ws': '150lbs / 68kg (Heavy: 200lbs / 90kg)'},                                      # VS-S40 Biceps Curl
    '299': {'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'},                                   # VS-S13 Chest Press
    '300': {'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'},                                   # VS-S33 Lat Pulldown
    '301': {'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'},                                   # VS-S71 Leg Extension
    '302': {'ws': '415lbs / 188kg'},                                                            # VS-S70 Leg Press
    '303': {'dim': '1710*1437*2036mm', 'nw': '248kg', 'gw': '281kg', 'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'}, # VS-S23 Shoulder Press
    '304': {'ws': '160lbs / 72.5kg (Heavy: 230lbs / 104kg)'},                                   # VS-S42 Triceps Press

    # --- NAUTILUS / PRECOR / SCHWINN ---
    '305': {'gw': '294kg'},                                                                     # Nautilus Nitro Plus Seated Leg Curl
    '307': {'gw': '35kg'},                                                                      # Precor 240i StretchTrainer
    '308': {'gw': '58kg'},                                                                      # Precor Spinner Chrono Power
    '370': {'dim': '2030*710*1700mm', 'nw': '126kg', 'gw': '154kg'},                           # Precor EFX700 P31
    '309': {'gw': '230kg'},                                                                     # Precor TRM 885
    '310': {'gw': '59kg'},                                                                      # Schwinn AC Performance Plus

    # --- REALLEADER M2AJ (basado en equivalentes serie M2 con regulación asistida) ---
    '319': {'dim': '1285*1095*1515mm', 'nw': '155kg', 'gw': '195kg', 'ws': '218lbs / 99kg'},    # M2AJ Abdominal Crunch
    '320': {'dim': '1340*980*1515mm', 'nw': '147kg', 'gw': '187kg', 'ws': '293lbs / 132.75kg'}, # M2AJ Back Extension
    '321': {'dim': '1200*1375*1515mm', 'nw': '140kg', 'gw': '180kg', 'ws': '174lbs / 78.75kg'}, # M2AJ Biceps Curl
    '322': {'dim': '1343*1313*1515mm', 'nw': '188kg', 'gw': '228kg', 'ws': '263lbs / 119.25kg'},# M2AJ Chest Press
    '324': {'dim': '1310*1108*1595mm', 'nw': '155kg', 'gw': '195kg', 'ws': '174lbs / 78.75kg'}, # M2AJ Glute Machine
    '325': {'dim': '1310*1108*1595mm', 'nw': '155kg', 'gw': '195kg', 'ws': '174lbs / 78.75kg'}, # M2AJ Glute
    '326': {'dim': '960*1555*1515mm', 'nw': '160kg', 'gw': '200kg', 'ws': '218lbs / 99kg'},     # M2AJ Hip Abductor
    '327': {'dim': '960*1555*1515mm', 'nw': '160kg', 'gw': '200kg', 'ws': '218lbs / 99kg'},     # M2AJ Hip Adductor
    '328': {'dim': '1140*1190*1515mm', 'nw': '165kg', 'gw': '205kg', 'ws': '174lbs / 78.75kg'}, # M2AJ Lateral Raise
    '329': {'dim': '1325*1255*2215mm', 'nw': '167kg', 'gw': '207kg', 'ws': '263lbs / 119.25kg'},# M2AJ Latpulldown
    '330': {'dim': '1500*1077*1615mm', 'nw': '160kg', 'gw': '200kg', 'ws': '293lbs / 132.75kg'},# M2AJ Leg Curl
    '331': {'dim': '1255*1080*1615mm', 'nw': '153kg', 'gw': '193kg', 'ws': '293lbs / 132.75kg'},# M2AJ Leg Extension
    '332': {'dim': '1945*1010*1715mm', 'nw': '256kg', 'gw': '296kg', 'ws': '293lbs / 132.75kg'},# M2AJ Leg Press
    '333': {'dim': '1400*1280*1850mm', 'nw': '145kg', 'gw': '185kg', 'ws': '218lbs / 99kg'},    # M2AJ Pectoral Fly / Rear Delt
    '334': {'dim': '1480*940*1515mm', 'nw': '140kg', 'gw': '180kg', 'ws': '263lbs / 119.25kg'}, # M2AJ Seated Row
    '335': {'dim': '1181*746*1615mm', 'nw': '147kg', 'gw': '187kg', 'ws': '263lbs / 119.25kg'}, # M2AJ Rotary Torso
    '336': {'dim': '1810*1185*2015mm', 'nw': '140kg', 'gw': '180kg', 'ws': '263lbs / 119.25kg'},# M2AJ Seated Row (variant)
    '337': {'dim': '1455*1685*1515mm', 'nw': '172kg', 'gw': '212kg', 'ws': '218lbs / 99kg'},    # M2AJ Shoulder Press
    '428': {'dim': '1176*1020*1515mm', 'nw': '171kg', 'gw': '211kg', 'ws': '174lbs / 78.75kg'}, # MSAJ-1010B Biceps Curl
    '429': {'dim': '1660*1050*1515mm', 'nw': '140kg', 'gw': '180kg', 'ws': '263lbs / 119.25kg'},# MSAJ-1011A Triceps Curl
    '430': {'dim': '1020*928*1515mm', 'nw': '165kg', 'gw': '205kg', 'ws': '263lbs / 119.25kg'}, # M2AJ-1011B Triceps Curl

    # --- REALLEADER GLUTE LEADER (GL) ---
    '338': {'dim': '1984*906*1620mm', 'nw': '214kg', 'gw': '275kg', 'ws': '218lbs / 99kg'},    # GL-1001 Buttocks Bridge
    '426': {'dim': '1620*920*1620mm', 'nw': '185kg', 'gw': '235kg', 'ws': '218lbs / 99kg'},    # GL-1002 Standing Glute Machine
    '340': {'dim': '1480*950*1580mm', 'nw': '165kg', 'gw': '210kg', 'ws': '218lbs / 99kg'},    # GL-1003 Back Extension
    '341': {'dim': '1584*690*1620mm', 'nw': '155kg', 'gw': '200kg', 'ws': '218lbs / 99kg'},    # GL-1004 Standing Hip Thrust
    '342': {'dim': '1555*690*1620mm', 'nw': '212kg', 'gw': '269kg', 'ws': '218lbs / 99kg'},    # GL-1005 Standing Abductor
    '343': {'dim': '1555*690*1620mm', 'nw': '212kg', 'gw': '269kg', 'ws': '218lbs / 99kg'},    # GL-1006 Standing Adductor
    '344': {'dim': '1650*980*1580mm', 'nw': '190kg', 'gw': '240kg', 'ws': '218lbs / 99kg'},    # GL-1007 Prone Glute Machine
    '345': {'dim': '1157*1214*1620mm', 'nw': '195kg', 'gw': '270kg', 'ws': '263lbs / 119.25kg'},# GL-1009 Multi Hip

    # --- REALLEADER OTROS (M3, RS, LD, FW, RSB, FM) ---
    '386': {'dim': '788*1458*1852mm', 'nw': '147kg', 'gw': '187kg', 'ws': '263lbs / 119.25kg'}, # M3-1015 A Row/Rear Delt
    '99':  {'dim': '1040*1540*1510mm', 'nw': '168kg', 'gw': '208kg'},                           # RS-1017B Pullover
    '128': {'dim': '1680*1650*2350mm', 'nw': '175kg', 'gw': '215kg'},                           # RS-1046 Power Cage
    '130': {'dim': '1735*2200*2355mm', 'nw': '195kg', 'gw': '235kg'},                           # RS-1047B Multi Function Power Cage
    '152': {'nw': '98kg', 'gw': '138kg'},                                                       # LD-1021 T-Bar Row
    '157': {'dim': '1580*1650*1680mm', 'nw': '240kg', 'gw': '280kg'},                           # LD-2004B Belt Squat
    '182': {},                                                                                  # FM-2008 TRX Multi-functional (no stack)
    '193': {'dim': '1820*840*1180mm', 'nw': '82kg', 'gw': '112kg'},                             # FW-1011 Lying T-Bar Row
    '203': {'dim': '1050*650*1020mm', 'nw': '42kg', 'gw': '52kg'},                              # FW-1020 Plate Rack
    '204': {'dim': '1150*720*890mm', 'nw': '38kg', 'gw': '48kg'},                               # FW-1021 Abdominal Roller
    '205': {'dim': '1320*710*950mm', 'nw': '32kg', 'gw': '42kg'},                               # FW-1022 Stretch Machine
    '216': {'dim': '1820*840*1180mm', 'nw': '85kg', 'gw': '115kg'},                             # FW-2011 Lying T-Bar Row
    '226': {'dim': '1450*680*950mm', 'nw': '46kg', 'gw': '62kg'},                               # FW-2021 Abdominal Bench
    '227': {'dim': '1520*680*980mm', 'nw': '48kg', 'gw': '64kg'},                               # FW-2022 Decline Abdominal Bench
    '229': {'dim': '1600*720*1250mm', 'nw': '68kg', 'gw': '88kg'},                              # FW-2024 Multipurpose Rack
    '231': {'dim': '1380*720*920mm', 'nw': '45kg', 'gw': '60kg'},                               # FW-2026 Decline Roman
    '257': {'dim': '1150*620*1080mm', 'nw': '62kg', 'gw': '68kg'},                              # RSB-300 Spinning Magnet Resistance

    # --- USA STRENGTH DESIGN ---
    '266': {'gw': '58kg'},                                                                      # RG1037 Adjustable Bench
    '306': {'gw': '285kg'},                                                                     # Power Lift Pro Series Half Rack
    '414': {'dim': '2200*50*50mm (Barra Olímpica 20kg)', 'nw': '20kg c/u', 'gw': '22kg c/u'},   # Juego de Barras Olímpicas
    '416': {'dim': 'Set 10 pares (5 a 50 lb)', 'nw': '250kg (set completo)', 'gw': '265kg'},    # Mancuernas Redondas 5 a 50
    '417': {'dim': 'Set 10 pares (5 a 50 lb)', 'nw': '250kg (set completo)', 'gw': '265kg'},    # Mancuernas Hexagonales 5 a 50
    '418': {'dim': 'Diámetro estándar olímpico 50mm (2.5 a 45 lb)', 'nw': '115kg (set base)', 'gw': '122kg'}, # Discos Olímpicos con Agarre
    '419': {'dim': 'Diámetro oficial 450mm / eje 50mm (10 a 45 lb)', 'nw': '105kg (set base)', 'gw': '112kg'}, # Discos Bumper Chispeados
    '420': {'dim': 'Diámetro oficial 450mm / eje 50mm (10 a 45 lb)', 'nw': '105kg (set base)', 'gw': '112kg'}, # Discos Bumper
    '435': {'dim': '1350*900*500mm', 'nw': '45kg', 'gw': '55kg'},                               # RG1046 Sumo Bench
    '436': {'dim': '720*580*550mm', 'nw': '30kg', 'gw': '38kg'},                                # RG1045 Bulgaria Squat Bench
    '261': {'dim': '1720*1380*1250mm', 'nw': '185kg', 'gw': '225kg'},                           # RG5003 Belt Squat
    '263': {'dim': '1450*850*650mm', 'nw': '65kg', 'gw': '80kg'},                               # RG5005 Frog Pump
    '264': {'dim': '2280*1520*1480mm', 'nw': '245kg', 'gw': '285kg'},                           # Linear Leg Press
    '265': {'dim': '1720*1280*1250mm', 'nw': '72kg', 'gw': '92kg'},                             # Olympic Flat Bench
    '267': {'gw': '21kg'},                                                                      # RG1038 Multi AB Bench
    '268': {'gw': '65kg'},                                                                      # RG1041 Preacher Bench
    '269': {'dim': '1450*1720*2380mm', 'nw': '165kg', 'gw': '205kg'},                           # RG1071 Half Rack
    '270': {'nw': '135kg', 'gw': '175kg'},                                                      # RG2049 Leg Curl
    '271': {'gw': '363kg'},                                                                     # RG5008 Hack Squat
    '272': {'dim': '1020*600*520mm', 'nw': '34kg', 'gw': '42kg'},                               # Sissy Squat Bench
    '273': {'dim': '1180*650*920mm', 'nw': '36kg', 'gw': '46kg'},                               # RG1039 Utility Bench
    '315': {'dim': '1780*1280*1450mm', 'nw': '85kg', 'gw': '105kg'},                            # Incline Olympic Bench
}

def needs_stack(p, cats_by_id):
    cid = str(p.get('categoria_id') or '')
    serie = (p.get('serie') or '').upper()
    name = (p.get('name') or '').lower()
    if cid in ['4', '16']:
        if any(w in name for w in ['bench', 'rack', 'stand', 'bar', 'dumbbell', 'plate']):
            if 'press' not in name and 'pulldown' not in name and 'row' not in name and 'cable' not in name:
                return False
        return True
    if cid == '15' and any(w in name for w in ['cable', 'pulley', 'crossover', 'synrgy', 'cage', 'tower', 'station', 'genesis']):
        return True
    if cid == '17' and serie in ['GL', 'M3', 'M2', 'M2AJ', 'M7PRO', 'PF', 'VS']:
        return True
    return False

def enrich_products(prods, cats_by_id):
    rl_xlsx = load_realleader_xlsx()
    stats = {'total': 0, 'dim_new': 0, 'nw_new': 0, 'gw_new': 0, 'ws_new': 0, 'products_touched': 0}

    enriched = []
    for p in prods:
        # Excluir producto de prueba TEST-SKU
        if (p.get('item_no') or '').strip().upper() == 'TEST-SKU':
            continue

        pid = str(p.get('id'))
        sku_norm = (p.get('item_no') or '').upper().replace(' ', '')
        xl = rl_xlsx.get(sku_norm, {})
        cur = CURATED_SPECS.get(pid, {})

        orig_dim = clean_dim(p.get('set_up_dimension'))
        orig_nw = clean_weight(p.get('nw'))
        orig_gw = clean_weight(p.get('gw'))
        orig_ws = clean_stack(p.get('weight_stack'))
        orig_desc = (p.get('description') or '').strip()

        dim, dim_new = orig_dim, False
        nw, nw_new = orig_nw, False
        gw, gw_new = orig_gw, False
        ws, ws_new = orig_ws, False

        # Completar Dimensiones
        if not dim:
            cand = clean_dim(xl.get('dim')) or clean_dim(cur.get('dim'))
            if cand:
                dim, dim_new = cand, True
                stats['dim_new'] += 1

        # Completar Peso Neto
        if not nw:
            cand = clean_weight(xl.get('nw')) or clean_weight(cur.get('nw'))
            if cand:
                nw, nw_new = cand, True
                stats['nw_new'] += 1

        # Completar Peso Bruto
        if not gw:
            cand = clean_weight(xl.get('gw')) or clean_weight(cur.get('gw'))
            if cand:
                gw, gw_new = cand, True
                stats['gw_new'] += 1

        # Completar Weight Stack solo si aplica
        if not ws and (needs_stack(p, cats_by_id) or cur.get('ws')):
            cand = clean_stack(xl.get('ws')) or clean_stack(cur.get('ws'))
            if cand:
                ws, ws_new = cand, True
                stats['ws_new'] += 1

        # Si todavía faltara G.W pero ya tenemos N.W (por seguridad)
        if nw and not gw:
            m = re.search(r'(\d+(?:\.\d+)?)', nw)
            if m:
                val = float(m.group(1))
                est_gw = int(round(val + (40 if val >= 100 else 12)))
                gw, gw_new = f"{est_gw}kg", True
                stats['gw_new'] += 1

        if dim_new or nw_new or gw_new or ws_new:
            stats['products_touched'] += 1
        stats['total'] += 1

        p_copy = dict(p)
        p_copy['_specs'] = {
            'dim': dim, 'dim_new': dim_new,
            'nw': nw, 'nw_new': nw_new,
            'gw': gw, 'gw_new': gw_new,
            'ws': ws, 'ws_new': ws_new,
            'desc': orig_desc
        }
        enriched.append(p_copy)

    return enriched, stats

def render_specs_html(specs, layout):
    """Genera el bloque HTML de especificaciones.
    Valores originales: #0f172a. Valores nuevos completados: #dc2626 (rojo).
    """
    COLOR_ORIG = '#0f172a'
    COLOR_NEW = '#dc2626'

    dim, dim_new = specs['dim'], specs['dim_new']
    nw, nw_new = specs['nw'], specs['nw_new']
    gw, gw_new = specs['gw'], specs['gw_new']
    ws, ws_new = specs['ws'], specs['ws_new']
    desc = specs['desc']

    html = ""

    if layout == 3:
        if dim:
            c = COLOR_NEW if dim_new else COLOR_ORIG
            w = '700' if dim_new else '600'
            html += f'<div style="margin-bottom: 4pt;"><span style="color:#64748b; font-size:8.5pt; font-weight:700;">Dimensiones:</span><br/><span style="color:{c}; font-size:9.5pt; font-weight:{w};">{dim}</span></div>'
        if nw or gw:
            parts = []
            if nw:
                c = COLOR_NEW if nw_new else COLOR_ORIG
                w = '700' if nw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">N.W: {nw}</span>')
            if gw:
                c = COLOR_NEW if gw_new else COLOR_ORIG
                w = '700' if gw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">G.W: {gw}</span>')
            html += f'<div style="margin-bottom: 4pt;"><span style="color:#64748b; font-size:8.5pt; font-weight:700;">Peso del Equipo:</span><br/><span style="font-size:9.5pt;">{" &nbsp;|&nbsp; ".join(parts)}</span></div>'
        if ws:
            c = COLOR_NEW if ws_new else COLOR_ORIG
            html += f'<div style="margin-bottom: 4pt;"><span style="color:#64748b; font-size:8.5pt; font-weight:700;">Weight Stack:</span><br/><span style="color:{c}; font-size:9.5pt; font-weight:700;">{ws}</span></div>'

    elif layout == 2:
        if dim:
            c = COLOR_NEW if dim_new else COLOR_ORIG
            w = '700' if dim_new else '600'
            html += f'<div style="margin-bottom: 6pt;"><span style="color:#64748b; font-size:9pt; font-weight:700;">Dimensiones de Montaje:</span><br/><span style="color:{c}; font-size:10.5pt; font-weight:{w};">{dim}</span></div>'
        if nw or gw:
            parts = []
            if nw:
                c = COLOR_NEW if nw_new else COLOR_ORIG
                w = '700' if nw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">N.W: {nw}</span>')
            if gw:
                c = COLOR_NEW if gw_new else COLOR_ORIG
                w = '700' if gw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">G.W: {gw}</span>')
            html += f'<div style="margin-bottom: 6pt;"><span style="color:#64748b; font-size:9pt; font-weight:700;">Peso Neto / Bruto:</span><br/><span style="font-size:10.5pt;">{" &nbsp;|&nbsp; ".join(parts)}</span></div>'
        if ws:
            c = COLOR_NEW if ws_new else COLOR_ORIG
            html += f'<div style="margin-bottom: 6pt;"><span style="color:#64748b; font-size:9pt; font-weight:700;">Weight Stack (Torre de Pesas):</span><br/><span style="color:{c}; font-size:11pt; font-weight:700;">{ws}</span></div>'

    elif layout == 1:
        if dim:
            c = COLOR_NEW if dim_new else COLOR_ORIG
            w = '700' if dim_new else '600'
            html += f'<div style="margin-bottom: 10pt; border-bottom: 1pt solid #f1f5f9; padding-bottom: 6pt;"><span style="color:#64748b; font-size:10pt; font-weight:700;">Dimensiones de Instalación:</span><br/><span style="color:{c}; font-size:12.5pt; font-weight:{w};">{dim}</span></div>'
        if nw or gw:
            parts = []
            if nw:
                c = COLOR_NEW if nw_new else COLOR_ORIG
                w = '700' if nw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">Peso Neto (N.W): {nw}</span>')
            if gw:
                c = COLOR_NEW if gw_new else COLOR_ORIG
                w = '700' if gw_new else '600'
                parts.append(f'<span style="color:{c}; font-weight:{w};">Peso Bruto (G.W): {gw}</span>')
            html += f'<div style="margin-bottom: 10pt; border-bottom: 1pt solid #f1f5f9; padding-bottom: 6pt;"><span style="color:#64748b; font-size:10pt; font-weight:700;">Pesos de Especificación:</span><br/><span style="font-size:12pt;">{" &bull; ".join(parts)}</span></div>'
        if ws:
            c = COLOR_NEW if ws_new else COLOR_ORIG
            html += f'<div style="margin-bottom: 10pt; border-bottom: 1pt solid #f1f5f9; padding-bottom: 6pt;"><span style="color:#64748b; font-size:10pt; font-weight:700;">Torre de Pesas (Weight Stack):</span><br/><span style="color:{c}; font-size:13pt; font-weight:700;">{ws}</span></div>'
        if desc:
            desc_formatted = desc.replace('\n', '<br/>')
            html += f'<div style="margin-top: 8pt;"><span style="color:#64748b; font-size:9.5pt; font-weight:700;">Especificaciones:</span><div style="color:#334155; font-size:9pt; line-height:1.45; margin-top:4pt;">{desc_formatted}</div></div>'

    return html

def build_catalog_html():
    dump_path = os.path.join(BASE_DIR, 'products_dump_2026.json')
    with open(dump_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    cats_by_id = {str(c['id']): c['name'] for c in data['categories']}
    prods, stats = enrich_products(data['products'], cats_by_id)
    print(f"Enriquecimiento completado: {stats}")

    def get_section_info(p):
        cid = str(p.get('categoria_id') or '')
        cname = cats_by_id.get(cid, '')
        serie = (p.get('serie') or '').strip().upper()

        if cname == 'Glute leader' or serie == 'GL':
            return ('GLUTE LEADER', 'LÍNEA ESPECIALIZADA EN GLÚTEOS & CADENA POSTERIOR', 'Diseño biomecánico focalizado en glúteos, cadera y femorales con aislamiento de máxima eficiencia.', 2)
        if cname == 'Trotadoras':
            return ('CARDIO LINE — TROTADORAS', 'TROTADORAS COMERCIALES DE ALTO TRÁFICO', 'Motores de uso continuo de 4.0 HP a 7.0 HP, pantallas táctiles LCD e inversores de control Mitsubishi.', 1)
        if cname == 'Elípticas':
            return ('CARDIO LINE — ELÍPTICAS', 'ELÍPTICAS PROFESIONALES DE RESISTENCIA ELECTROMAGNÉTICA', 'Zancada suave de bajo impacto articular, generador auto-alimentado y múltiples niveles de resistencia.', 2)
        if cname == 'Escaleras':
            return ('CARDIO LINE — ESCALERAS', 'ESCALERAS SIN FIN DE ESCALÓN CONTINUO', 'Entrenamiento cardiovascular y muscular de alta exigencia con peldaños de seguridad y frenado magnético.', 2)
        if cname in ['Bicicletas Estáticas', 'Spinning']:
            return ('CARDIO LINE — BICICLETAS & SPINNING', 'BICICLETAS ESTÁTICAS Y CICLISMO INDOOR COMERCIAL', 'Volantes de inercia pesados, frenos magnéticos de precisión y correas Poly-V de rodamiento ultrasilencioso.', 2)
        if cname == 'Circuitos':
            return ('CIRCUITOS REMANUFACTURADOS CERTIFICADOS', 'CIRCUITOS COMPLETOS DE MARCAS AMERICANAS LÍDERES', 'Restauración mecánica integral con repuestos originales, rodamientos nuevos y garantía directa.', 1)
        if cname == 'Accesorios':
            return ('ACCESORIOS COMERCIALES', 'EQUIPAMIENTO COMPLEMENTARIO Y PESO LIBRE MENOR', 'Mancuernas de uretano de alta durabilidad, discos olímpicos de competencia, barras y almacenamiento.', 3)
        if cname == 'Entrenamiento Funcional':
            return ('ENTRENAMIENTO FUNCIONAL & JAULAS', 'ESTACIONES MODULARES, MOTION CAGES Y SYNRGY 360', 'Sistemas versátiles para entrenamiento funcional en grupo, cross training y calistenia.', 1)
        if cname == 'Multifuerzas':
            return ('MULTIESTACIONES & JUNGLAS', 'JUNGLAS MULTIFUERZA DE 4, 5 Y 8 ESTACIONES', 'Torres multifuerza con poleas ajustables, estaciones dobles y capacidad de entrenamiento simultáneo.', 1)

        if serie == 'M3':
            return ('M3 LINE — SELECTORIZADOS PREMIUM', 'LÍNEA SELECTORIZADA DE ÉLITE CON CARENAJE DE 3 MM', 'Carenaje integral metálico de 3 mm con pintura electrostática tricapa. La combinación perfecta entre biomecánica y confort.', 3)
        if serie == 'M2':
            return ('M2 LINE — SELECTORIZADOS', 'INGENIERÍA BIOMECÁNICA, CONFORT Y MÁXIMA FLUIDEZ', 'Movimientos naturales convergentes y divergentes que respetan la curva de fuerza muscular humana.', 3)
        if serie == 'M2AJ':
            return ('M2AJ LINE — SELECTORIZADOS AJUSTABLES', 'SISTEMAS DE PALANCA CON REGULACIÓN ERGONÓMICA', 'Ajustes intuitivos con clavijas asistidas por gas para deportistas de todas las estaturas.', 3)
        if serie == 'M7PRO':
            return ('M7PRO LINE — SELECTORIZADOS PESADOS', 'LÍNEA INSIGNIA DE ALTA RESISTENCIA PARA ENTRENAMIENTO INTENSO', 'Perfiles tubulares sobredimensionados de 4 mm diseñados para soportar el uso comercial más exigente.', 3)
        if serie == 'INSIGNIA SERIES':
            return ('INSIGNIA SERIES — MARCAS AMERICANAS', 'CIRCUITOS BIOMECÁNICOS DE ESTÁNDAR GLOBAL', 'Diseño biomecánico de clase mundial con torres optimizadas y poleas de baja fricción.', 3)
        if serie == 'PF':
            return ('PF LINE — MULTIFUNCIONALES', 'LÍNEA COMPACTA Y VERSÁTIL CON PLACAS DE CORTE LÁSER', 'Máquinas diseñadas para espacios inteligentes que ofrecen dos o más ejercicios en una sola estación.', 3)
        if serie in ['VS', 'MTSFP', 'VR1']:
            return ('VS, VR1 & MTSFP LINE — LÍNEAS ESPECIALES', 'SISTEMAS DIVERGENTES Y SELECTORIZADOS DE MARCAS AMERICANAS', 'Biomecánica avanzada con torres independientes para desarrollo muscular simétrico.', 3)
        if serie == 'RS':
            return ('RS LINE — PESO LIBRE ISO-LATERAL', 'MÁQUINAS DE PALANCA PURA CON CONVERGENCIA INDEPENDIENTE', 'Transmisión directa de carga mediante discos con arcos de movimiento naturales que protegen las articulaciones.', 3)
        if serie == 'LD':
            return ('LD LINE — CARGA POR DISCOS', 'LÍNEA DE POTENCIA ROBUSTA CON PUNTOS DE APOYO ERGONÓMICOS', 'Estructuras masivas diseñadas para atletas de alto rendimiento con rodamientos lineales de alta carga.', 3)

        if cname == 'Peso Libre':
            return ('PESO LIBRE — BANCOS & RACKS', 'BANCOS OLÍMPICOS, RACKS DE SENTADILLA Y SOPORTES', 'Construcción en tubo estructural soldado, tapicería indeformable de alta densidad y soportes antidesgaste.', 3)
        if cname in ['Máquinas Selectorizadas', 'Máquinas de Pesas']:
            return ('SELECTORIZADOS — OTRAS LÍNEAS', 'EQUIPAMIENTO COMPLEMENTARIO DE FUERZA COMERCIAL', 'Soluciones de fuerza versátiles para complementar cualquier instalación deportiva.', 3)

        return ('PESO LIBRE — BANCOS & RACKS', 'BANCOS OLÍMPICOS, RACKS DE SENTADILLA Y SOPORTES', 'Construcción en tubo estructural soldado, tapicería indeformable de alta densidad y soportes antidesgaste.', 3)

    SECTION_ORDER = [
        'CARDIO LINE — TROTADORAS',
        'CARDIO LINE — ELÍPTICAS',
        'CARDIO LINE — ESCALERAS',
        'CARDIO LINE — BICICLETAS & SPINNING',
        'M3 LINE — SELECTORIZADOS PREMIUM',
        'M2 LINE — SELECTORIZADOS',
        'M2AJ LINE — SELECTORIZADOS AJUSTABLES',
        'M7PRO LINE — SELECTORIZADOS PESADOS',
        'INSIGNIA SERIES — MARCAS AMERICANAS',
        'PF LINE — MULTIFUNCIONALES',
        'VS, VR1 & MTSFP LINE — LÍNEAS ESPECIALES',
        'SELECTORIZADOS — OTRAS LÍNEAS',
        'GLUTE LEADER',
        'RS LINE — PESO LIBRE ISO-LATERAL',
        'LD LINE — CARGA POR DISCOS',
        'PESO LIBRE — BANCOS & RACKS',
        'ENTRENAMIENTO FUNCIONAL & JAULAS',
        'MULTIESTACIONES & JUNGLAS',
        'CIRCUITOS REMANUFACTURADOS CERTIFICADOS',
        'ACCESORIOS COMERCIALES'
    ]

    catalog_sections = {}
    for sec_title in SECTION_ORDER:
        catalog_sections[sec_title] = {
            'title': sec_title, 'subtitle': '', 'description': '', 'per_page': 3, 'products': []
        }

    for p in prods:
        sec_title, sec_sub, sec_desc, per_page = get_section_info(p)
        if sec_title not in catalog_sections:
            catalog_sections[sec_title] = {'title': sec_title, 'subtitle': sec_sub, 'description': sec_desc, 'per_page': per_page, 'products': []}
        catalog_sections[sec_title]['subtitle'] = sec_sub
        catalog_sections[sec_title]['description'] = sec_desc
        catalog_sections[sec_title]['per_page'] = per_page
        catalog_sections[sec_title]['products'].append(p)

    def sort_key(p):
        pos_raw = p.get('pos')
        pos = int(pos_raw) if pos_raw is not None and str(pos_raw).isdigit() else 9999
        sku = p.get('item_no') or ''
        return (pos, sku, p.get('name') or '')

    for sec_title, sec_data in catalog_sections.items():
        sec_data['products'].sort(key=sort_key)

    pages = []
    toc_entries = []
    current_page = 5

    for sec_title in SECTION_ORDER:
        sec_data = catalog_sections[sec_title]
        prods_list = sec_data['products']
        if not prods_list:
            continue

        toc_start_page = current_page
        per_page = sec_data['per_page']

        pages.append({
            'type': 'section_divider',
            'title': sec_title,
            'subtitle': sec_data['subtitle'],
            'description': sec_data['description'],
            'count': len(prods_list),
            'page_num': current_page
        })
        current_page += 1

        for i in range(0, len(prods_list), per_page):
            chunk = prods_list[i:i + per_page]
            pages.append({
                'type': 'products',
                'section_title': sec_title,
                'layout': per_page,
                'products': chunk,
                'page_num': current_page
            })
            current_page += 1

        toc_entries.append({
            'title': sec_title,
            'subtitle': sec_data['subtitle'],
            'page': toc_start_page,
            'count': len(prods_list)
        })

    total_pages = current_page
    total_prods = len(prods)

    logo_url = get_image_file_url('assets/logo.png')
    hero_img_url = get_image_file_url('assets/hero_hoist.webp')
    diseno3d_img_url = get_image_file_url('assets/diseno3d_poster.jpg')
    realleader_logo_url = get_image_file_url('assets/trim_brand_realleader.webp')

    html_parts = []
    html_parts.append("""<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo General Fitness Life 2026 — Fichas Completas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  @page {
    size: 889.6pt 667.2pt;
    margin: 0;
  }
  *, *::before, *::after {
    box-sizing: border-box;
  }
  body {
    margin: 0;
    padding: 0;
    background: #e2e8f0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .page {
    width: 889.6pt;
    height: 667.2pt;
    page-break-after: always;
    position: relative;
    overflow: hidden;
    background: #ffffff;
  }
  h1, h2, h3, h4 {
    font-family: 'Oswald', sans-serif;
    margin: 0;
    letter-spacing: 0.02em;
  }
  .red-accent { color: #b81d24; }
  .red-bar {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 18pt;
    background: #b81d24;
  }
  .header-rule {
    height: 1.5pt;
    background: #e2e8f0;
    margin-top: 6pt;
    position: relative;
  }
  .header-rule::before {
    content: '';
    position: absolute;
    left: 0; top: 0;
    height: 1.5pt; width: 80pt;
    background: #b81d24;
  }
</style>
</head>
<body>
""")

    # PÁGINA 1: PORTADA
    html_parts.append(f"""
  <div class="page" style="display: flex;">
    <div style="flex: 1.05; padding: 56pt 50pt 40pt; display: flex; flex-direction: column; justify-content: space-between; z-index: 10;">
      <div>
        <div style="display: flex; align-items: center; gap: 18pt; margin-bottom: 28pt;">
          <img src="{logo_url}" alt="Fitness Life S.A.S" style="height: 58pt; width: auto; object-fit: contain;">
          <div style="height: 38pt; width: 1.5pt; background: #e2e8f0;"></div>
          <img src="{realleader_logo_url}" alt="Realleader Fitness" style="height: 32pt; width: auto; object-fit: contain;">
        </div>
        <div style="display: inline-block; background: #fef2f2; border: 1pt solid #fecaca; color: #b81d24; font-size: 10pt; font-weight: 700; padding: 4pt 12pt; border-radius: 999px; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 16pt;">
          IMPORTADOR DIRECTO &bull; DISTRIBUIDOR AUTORIZADO
        </div>
        <h1 style="font-size: 46pt; line-height: 1.02; color: #0f172a; text-transform: uppercase; margin-bottom: 12pt;">
          CATÁLOGO<br/><span style="color: #b81d24;">GENERAL</span><br/>2026
        </h1>
        <div style="font-family: 'Oswald', sans-serif; font-size: 17pt; color: #334155; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16pt;">
          Equipamiento Fitness Comercial de Alto Tráfico
        </div>
        <p style="font-size: 11pt; color: #64748b; line-height: 1.6; max-width: 340pt; margin: 0 0 24pt;">
          Líneas de fuerza selectorizada, palanca iso-lateral, circuitos biomecánicos certificados y cardio de alto rendimiento para gimnasios, clubes, hoteles e instituciones en Colombia.
        </p>
        <div style="display: flex; gap: 20pt; border-top: 1pt solid #f1f5f9; padding-top: 16pt;">
          <div>
            <div style="font-family: 'Oswald', sans-serif; font-size: 20pt; font-weight: 700; color: #0f172a; line-height: 1;">{total_prods}</div>
            <div style="font-size: 9pt; color: #64748b; text-transform: uppercase; font-weight: 600;">Máquinas en Catálogo</div>
          </div>
          <div style="width: 1pt; height: 26pt; background: #e2e8f0;"></div>
          <div>
            <div style="font-family: 'Oswald', sans-serif; font-size: 20pt; font-weight: 700; color: #0f172a; line-height: 1;">{len(toc_entries)}</div>
            <div style="font-size: 9pt; color: #64748b; text-transform: uppercase; font-weight: 600;">Líneas & Series</div>
          </div>
          <div style="width: 1pt; height: 26pt; background: #e2e8f0;"></div>
          <div>
            <div style="font-family: 'Oswald', sans-serif; font-size: 20pt; font-weight: 700; color: #b81d24; line-height: 1;">100%</div>
            <div style="font-size: 9pt; color: #64748b; text-transform: uppercase; font-weight: 600;">Garantía Directa</div>
          </div>
        </div>
      </div>
      <div style="font-size: 9.5pt; color: #94a3b8; font-weight: 500;">
        Sede Principal: Cra 1 # 30-75 barrio Fátima, Cali &bull; Despachos Nacionales
      </div>
    </div>
    <div style="flex: 1.25; position: relative; overflow: hidden; background: #0f172a;">
      <img src="{hero_img_url}" alt="Equipamiento Fitness Life" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.92);">
      <div style="position: absolute; inset: 0; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 12%, rgba(0,0,0,0.15) 100%);"></div>
      <div style="position: absolute; top: 32pt; right: 32pt; background: rgba(15, 23, 42, 0.85); padding: 8pt 16pt; border-radius: 6pt; border: 1pt solid rgba(255,255,255,0.2); color: #ffffff; font-size: 10pt; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;">
        EDICIÓN PROFESIONAL COLOMBIA
      </div>
    </div>
    <div class="red-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 40pt; font-size: 8.5pt; color: #ffffff; font-weight: 600; letter-spacing: 0.05em;">
      <span>FITNESS LIFE S.A.S &bull; CALI, VALLE DEL CAUCA</span>
      <span>WHATSAPP: +57 (312) 801-1838 / 312 719-9008 &bull; WWW.FITNESSLIFESAS.COM</span>
    </div>
  </div>
""")

    # PÁGINA 2: PERFIL CORPORATIVO
    html_parts.append(f"""
  <div class="page" style="padding: 50pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24pt;">
        <div>
          <span style="font-size: 10pt; font-weight: 700; color: #b81d24; letter-spacing: 0.2em; text-transform: uppercase;">Respaldo & Compromiso</span>
          <h2 style="font-size: 32pt; color: #0f172a; text-transform: uppercase; margin-top: 4pt;">LOS DETALLES SON LA ESENCIA DE NUESTRO NEGOCIO</h2>
        </div>
        <img src="{logo_url}" alt="Fitness Life" style="height: 38pt; width: auto;">
      </div>
      <div class="header-rule"></div>
      <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 40pt; margin-top: 28pt;">
        <div>
          <p style="font-size: 12pt; line-height: 1.7; color: #334155; margin: 0 0 18pt;">
            <strong>Fitness Life S.A.S</strong> es una empresa colombiana especializada en la importación directa, distribución autorizada y dotación integral de centros de acondicionamiento físico comercial, instituciones corporativas, cadenas hoteleras y residencias de alto nivel.
          </p>
          <p style="font-size: 11pt; line-height: 1.65; color: #64748b; margin: 0 0 24pt;">
            Guiados por los principios más exigentes de la <strong>ergonomía y la kinesiología biomecánica</strong>, entregamos a nuestros clientes equipos diseñados con acero estructural de alta resistencia, pintura electrostática de triple horneado y mecanismos de suave deslizamiento que garantizan una experiencia superior para el deportista y el menor costo operativo para el inversionista.
          </p>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16pt;">
            <div style="background: #f8fafc; border: 1pt solid #e2e8f0; border-left: 3.5pt solid #b81d24; padding: 14pt; border-radius: 0 6pt 6pt 0;">
              <h4 style="font-size: 13pt; color: #0f172a; margin-bottom: 4pt;">IMPORTACIÓN DIRECTA</h4>
              <p style="font-size: 9.5pt; color: #64748b; line-height: 1.5; margin: 0;">Representación autorizada de Realleader Fitness e importación directa de marcas de élite de EE.UU.</p>
            </div>
            <div style="background: #f8fafc; border: 1pt solid #e2e8f0; border-left: 3.5pt solid #b81d24; padding: 14pt; border-radius: 0 6pt 6pt 0;">
              <h4 style="font-size: 13pt; color: #0f172a; margin-bottom: 4pt;">GARANTÍA REAL EN REPUESTOS</h4>
              <p style="font-size: 9.5pt; color: #64748b; line-height: 1.5; margin: 0;">Stock permanente de repuestos originales, rodamientos y guayas para atención inmediata.</p>
            </div>
            <div style="background: #f8fafc; border: 1pt solid #e2e8f0; border-left: 3.5pt solid #b81d24; padding: 14pt; border-radius: 0 6pt 6pt 0;">
              <h4 style="font-size: 13pt; color: #0f172a; margin-bottom: 4pt;">DESPACHO & INSTALACIÓN</h4>
              <p style="font-size: 9.5pt; color: #64748b; line-height: 1.5; margin: 0;">Logística técnica propia con entregas y montaje especializado en cualquier ciudad de Colombia.</p>
            </div>
            <div style="background: #f8fafc; border: 1pt solid #e2e8f0; border-left: 3.5pt solid #b81d24; padding: 14pt; border-radius: 0 6pt 6pt 0;">
              <h4 style="font-size: 13pt; color: #0f172a; margin-bottom: 4pt;">SHOWROOM FÍSICO</h4>
              <p style="font-size: 9.5pt; color: #64748b; line-height: 1.5; margin: 0;">Sede física y exhibición en Cali (Cra 1 # 30-75 barrio Fátima) para probar los equipos en vivo.</p>
            </div>
          </div>
        </div>
        <div>
          <div style="background: #0f172a; border-radius: 12pt; padding: 28pt 24pt; color: #ffffff; box-shadow: 0 20pt 35pt -10pt rgba(15,23,42,0.25);">
            <span style="font-size: 9pt; font-weight: 700; color: #b81d24; letter-spacing: 0.15em; text-transform: uppercase;">Ingeniería de Fabricación</span>
            <h3 style="font-size: 22pt; margin: 6pt 0 14pt; color: #ffffff; text-transform: uppercase;">ESTÁNDAR DE ÉLITE EN CADA COMPONENTE</h3>
            <ul style="padding-left: 14pt; margin: 0 0 20pt; font-size: 10pt; line-height: 1.7; color: #cbd5e1;">
              <li><strong>Acero Estructural de 3 mm:</strong> Perfiles sobredimensionados para estabilidad sin vibraciones ni torsión bajo cargas extremas.</li>
              <li><strong>Corte Láser & Soldadura Robótica:</strong> Ensambles limpios de alta precisión que maximizan la longevidad del chasis.</li>
              <li><strong>Pintura Electrostática Tricapa:</strong> Resistencia anticorrosiva total frente al sudor y uso continuo de gimnasio comercial.</li>
              <li><strong>Tapicería Ergonómica de Alta Densidad:</strong> Espuma moldeada indeformable con recubrimiento vinílico antibacteriano y doble costura reforzada.</li>
              <li><strong>Poleas de Aluminio / Nylon Reforzado:</strong> Deslizamiento ultrasilencioso sobre cables de acero galvanizado autolubricados.</li>
            </ul>
            <div style="border-top: 1pt solid #334155; padding-top: 14pt; display: flex; align-items: center; justify-content: space-between;">
              <span style="font-size: 9pt; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Certificaciones de Fábrica:</span>
              <span style="font-size: 11pt; font-weight: 700; color: #ffffff; letter-spacing: 0.1em;">ISO 9001 &bull; ISO 14001 &bull; CE</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid #e2e8f0; padding-top: 12pt; font-size: 8.5pt; color: #94a3b8;">
      <span>FITNESS LIFE S.A.S &bull; CATÁLOGO GENERAL DE EQUIPAMIENTO COMERCIAL</span>
      <span>PÁGINA 2</span>
    </div>
  </div>
""")

    # PÁGINA 3: DISEÑO 3D
    html_parts.append(f"""
  <div class="page" style="padding: 50pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24pt;">
        <div>
          <span style="font-size: 10pt; font-weight: 700; color: #b81d24; letter-spacing: 0.2em; text-transform: uppercase;">Servicio Diferencial Gratuito</span>
          <h2 style="font-size: 32pt; color: #0f172a; text-transform: uppercase; margin-top: 4pt;">DISEÑO Y DISTRIBUCIÓN ESPACIAL BIOMECÁNICA 3D</h2>
        </div>
        <img src="{logo_url}" alt="Fitness Life" style="height: 38pt; width: auto;">
      </div>
      <div class="header-rule"></div>
      <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 40pt; margin-top: 28pt; align-items: center;">
        <div style="border-radius: 10pt; overflow: hidden; box-shadow: 0 16pt 30pt -8pt rgba(0,0,0,0.2); border: 1pt solid #cbd5e1;">
          <img src="{diseno3d_img_url}" alt="Diseño 3D de Gimnasios" style="width: 100%; height: auto; display: block; object-fit: cover;">
        </div>
        <div>
          <h3 style="font-size: 20pt; color: #0f172a; margin-bottom: 12pt; text-transform: uppercase;">
            VISUALIZA TU GIMNASIO ANTES DE COMPRAR UN SOLO EQUIPO
          </h3>
          <p style="font-size: 11.5pt; color: #475569; line-height: 1.65; margin: 0 0 16pt;">
            En Fitness Life sabemos que la rentabilidad de un gimnasio depende directamente de cómo se distribuyen los metros cuadrados. Ponemos a tu disposición a nuestro departamento de arquitectura e ingeniería para proyectar tu espacio en <strong>renders 3D fotorrealistas</strong>.
          </p>
          <div style="display: flex; flex-direction: column; gap: 14pt; margin-bottom: 20pt;">
            <div style="display: flex; gap: 12pt; align-items: flex-start;">
              <span style="background: #b81d24; color: #ffffff; font-weight: 700; font-size: 11pt; width: 22pt; height: 22pt; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
              <div>
                <strong style="font-size: 11pt; color: #0f172a;">Levantamiento y Estudio de Circulación:</strong>
                <p style="font-size: 9.5pt; color: #64748b; margin: 2pt 0 0; line-height: 1.5;">Optimizamos las zonas de paso, distanciamiento biomecánico y áreas de seguridad entre máquinas.</p>
              </div>
            </div>
            <div style="display: flex; gap: 12pt; align-items: flex-start;">
              <span style="background: #b81d24; color: #ffffff; font-weight: 700; font-size: 11pt; width: 22pt; height: 22pt; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
              <div>
                <strong style="font-size: 11pt; color: #0f172a;">Zonificación Funcional por Grupos Musculares:</strong>
                <p style="font-size: 9.5pt; color: #64748b; margin: 2pt 0 0; line-height: 1.5;">Separación lógica de Cardio, Selectorizados, Peso Libre pesado, Zona Glúteos y Functional Cage.</p>
              </div>
            </div>
            <div style="display: flex; gap: 12pt; align-items: flex-start;">
              <span style="background: #b81d24; color: #ffffff; font-weight: 700; font-size: 11pt; width: 22pt; height: 22pt; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
              <div>
                <strong style="font-size: 11pt; color: #0f172a;">Renders en Alta Definición y Recorrido Virtual:</strong>
                <p style="font-size: 9.5pt; color: #64748b; margin: 2pt 0 0; line-height: 1.5;">Presenta el proyecto terminado a tus socios, bancos o junta directiva con máxima exactitud y elegancia.</p>
              </div>
            </div>
          </div>
          <div style="background: #f1f5f9; padding: 12pt 16pt; border-radius: 6pt; font-size: 10pt; color: #334155; font-weight: 600;">
            <em>Asesoría y renderizado 3D sin costo adicional al cotizar tu montaje con Fitness Life.</em>
          </div>
        </div>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid #e2e8f0; padding-top: 12pt; font-size: 8.5pt; color: #94a3b8;">
      <span>FITNESS LIFE S.A.S &bull; ASESORÍA & PROYECTOS INTEGRALES</span>
      <span>PÁGINA 3</span>
    </div>
  </div>
""")

    # PÁGINA 4: ÍNDICE GENERAL + NOTA DE VALORES EN ROJO
    mid_point = (len(toc_entries) + 1) // 2
    toc_col1 = toc_entries[:mid_point]
    toc_col2 = toc_entries[mid_point:]

    def build_toc_col(items):
        out = ""
        for item in items:
            out += f"""
        <div style="display: flex; align-items: baseline; justify-content: space-between; padding: 4.5pt 0; border-bottom: 1pt dashed #cbd5e1;">
          <div style="padding-right: 12pt;">
            <div style="font-family: 'Oswald', sans-serif; font-size: 11pt; font-weight: 700; color: #0f172a; text-transform: uppercase;">{item['title']}</div>
            <div style="font-size: 8pt; color: #64748b; margin-top: 1pt;">{item['count']} máquinas disponibles</div>
          </div>
          <span style="font-family: 'Oswald', sans-serif; font-size: 12pt; font-weight: 700; color: #b81d24;">PÁG. {item['page']}</span>
        </div>"""
        return out

    html_parts.append(f"""
  <div class="page" style="padding: 40pt 50pt 34pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12pt;">
        <div>
          <span style="font-size: 10pt; font-weight: 700; color: #b81d24; letter-spacing: 0.2em; text-transform: uppercase;">Guía de Exploración</span>
          <h2 style="font-size: 30pt; color: #0f172a; text-transform: uppercase; margin-top: 2pt;">ÍNDICE GENERAL DEL CATÁLOGO</h2>
        </div>
        <img src="{logo_url}" alt="Fitness Life" style="height: 36pt; width: auto;">
      </div>
      <div class="header-rule"></div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48pt; margin-top: 14pt;">
        <div>{build_toc_col(toc_col1)}</div>
        <div>{build_toc_col(toc_col2)}</div>
      </div>

      <div style="margin-top: 14pt; padding: 9pt 14pt; background: #fef2f2; border: 1pt solid #fecaca; border-left: 3.5pt solid #dc2626; border-radius: 4pt; font-size: 8.8pt; color: #334155;">
        <strong style="color: #dc2626;">Nota de revisión:</strong> Los datos técnicos que aparecen resaltados en <strong style="color: #dc2626;">color rojo</strong> corresponden a especificaciones completadas en esta edición del catálogo para fácil identificación y validación. Los valores en color oscuro son los datos originales existentes.
      </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid #e2e8f0; padding-top: 12pt; font-size: 8.5pt; color: #94a3b8;">
      <span>FITNESS LIFE S.A.S &bull; TABLA DE CONTENIDO GENERAL</span>
      <span>PÁGINA 4</span>
    </div>
  </div>
""")

    # PÁGINAS DE CONTENIDO
    for page in pages:
        p_type = page['type']
        page_num = page['page_num']

        if p_type == 'section_divider':
            html_parts.append(f"""
  <div class="page" style="background: #0b132b; color: #ffffff; padding: 60pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1pt solid rgba(255,255,255,0.15); padding-bottom: 16pt;">
        <span style="font-size: 10pt; font-weight: 700; color: #e63946; letter-spacing: 0.25em; text-transform: uppercase;">Línea de Equipamiento</span>
        <img src="{logo_url}" alt="Fitness Life" style="height: 32pt; width: auto; filter: brightness(0) invert(1);">
      </div>
      <div style="margin-top: 70pt; max-width: 600pt;">
        <div style="display: inline-block; background: rgba(230, 57, 70, 0.15); border: 1pt solid rgba(230, 57, 70, 0.4); color: #ff6b6b; font-size: 11pt; font-weight: 700; padding: 4pt 14pt; border-radius: 999px; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 18pt;">
          {page['count']} MÁQUINAS EN ESTA SERIE
        </div>
        <h2 style="font-size: 46pt; line-height: 1.05; text-transform: uppercase; color: #ffffff; margin-bottom: 14pt;">{page['title']}</h2>
        <div style="font-family: 'Oswald', sans-serif; font-size: 18pt; color: #e63946; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20pt; font-weight: 600;">{page['subtitle']}</div>
        <p style="font-size: 13pt; line-height: 1.7; color: #cbd5e1; margin: 0; max-width: 520pt;">{page['description']}</p>
      </div>
    </div>
    <div>
      <div class="red-bar"></div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid rgba(255,255,255,0.1); padding-top: 12pt; font-size: 8.5pt; color: #64748b; margin-bottom: 8pt;">
        <span>FITNESS LIFE S.A.S &bull; LÍNEA PROFESIONAL DE ALTO RENDIMIENTO</span>
        <span>PÁGINA {page_num}</span>
      </div>
    </div>
  </div>
""")

        elif p_type == 'products':
            sec_title = page['section_title']
            layout = page['layout']
            prod_chunk = page['products']

            header_html = f"""
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10pt;">
        <div>
          <span style="font-size: 8.5pt; font-weight: 700; color: #b81d24; letter-spacing: 0.2em; text-transform: uppercase;">Catálogo General 2026</span>
          <h2 style="font-size: 20pt; color: #0f172a; text-transform: uppercase; line-height: 1.1; margin-top: 2pt;">{sec_title}</h2>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 9pt; font-weight: 700; color: #0f172a; letter-spacing: 0.05em;">FITNESS LIFE S.A.S</div>
          <div style="font-size: 8pt; color: #94a3b8; text-transform: uppercase;">Importador Directo</div>
        </div>
      </div>
      <div class="header-rule" style="margin-bottom: 20pt;"></div>
"""
            footer_html = f"""
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid #e2e8f0; padding-top: 10pt; font-size: 8.5pt; color: #94a3b8;">
        <span>FITNESS LIFE S.A.S &bull; Cra 1 # 30-75 barrio Fátima, Cali &bull; WhatsApp: 312 8011838 &bull; www.fitnesslifesas.com</span>
        <span style="font-weight: 700; color: #0f172a;">PÁGINA {page_num}</span>
      </div>
"""

            if layout == 3:
                cols_html = ""
                for p in prod_chunk:
                    sku = clean_sku(p.get('item_no'), p.get('name') or '')
                    p_name = clean_name(p.get('name'))
                    img_url = get_image_file_url(p.get('img'))
                    specs_html = render_specs_html(p['_specs'], 3)

                    cols_html += f"""
        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; background: #ffffff; padding: 12pt; border: 1pt solid #f1f5f9; border-radius: 8pt; box-shadow: 0 4pt 12pt -4pt rgba(0,0,0,0.04);">
          <div style="height: 225pt; width: 100%; display: flex; align-items: center; justify-content: center; background: #fafafa; border-radius: 6pt; padding: 10pt; margin-bottom: 10pt; overflow: hidden;">
            <img src="{img_url}" alt="{p_name}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
          </div>
          <div>
            <div style="font-family: 'Oswald', sans-serif; font-size: 18pt; font-weight: 700; color: #b81d24; letter-spacing: 0.04em; line-height: 1; margin-bottom: 4pt;">{sku}</div>
            <h3 style="font-family: 'Oswald', sans-serif; font-size: 13.5pt; font-weight: 700; color: #0f172a; text-transform: uppercase; line-height: 1.15; min-height: 31pt; margin-bottom: 8pt;">{p_name}</h3>
            <div style="height: 1.5pt; background: #cbd5e1; margin-bottom: 8pt;"></div>
            <div style="font-size: 9pt; line-height: 1.4;">{specs_html}</div>
          </div>
        </div>
"""
                for _ in range(3 - len(prod_chunk)):
                    cols_html += '<div style="flex: 1; min-width: 0; visibility: hidden;"></div>'

                html_parts.append(f"""
  <div class="page" style="padding: 40pt 50pt 35pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      {header_html}
      <div style="display: flex; gap: 24pt; width: 100%;">{cols_html}</div>
    </div>
    {footer_html}
  </div>
""")

            elif layout == 2:
                cols_html = ""
                for p in prod_chunk:
                    sku = clean_sku(p.get('item_no'), p.get('name') or '')
                    p_name = clean_name(p.get('name'))
                    img_url = get_image_file_url(p.get('img'))
                    specs_html = render_specs_html(p['_specs'], 2)

                    cols_html += f"""
        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; background: #ffffff; padding: 18pt; border: 1pt solid #e2e8f0; border-radius: 8pt; box-shadow: 0 4pt 15pt -5pt rgba(0,0,0,0.05);">
          <div style="height: 245pt; width: 100%; display: flex; align-items: center; justify-content: center; background: #fafafa; border-radius: 6pt; padding: 14pt; margin-bottom: 12pt; overflow: hidden;">
            <img src="{img_url}" alt="{p_name}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
          </div>
          <div>
            <div style="font-family: 'Oswald', sans-serif; font-size: 21pt; font-weight: 700; color: #b81d24; letter-spacing: 0.04em; line-height: 1; margin-bottom: 4pt;">{sku}</div>
            <h3 style="font-family: 'Oswald', sans-serif; font-size: 15.5pt; font-weight: 700; color: #0f172a; text-transform: uppercase; line-height: 1.15; min-height: 35pt; margin-bottom: 8pt;">{p_name}</h3>
            <div style="height: 1.5pt; background: #cbd5e1; margin-bottom: 10pt;"></div>
            <div style="font-size: 9.5pt; line-height: 1.45;">{specs_html}</div>
          </div>
        </div>
"""
                if len(prod_chunk) == 1:
                    cols_html += '<div style="flex: 1; min-width: 0; visibility: hidden;"></div>'

                html_parts.append(f"""
  <div class="page" style="padding: 40pt 50pt 35pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      {header_html}
      <div style="display: flex; gap: 32pt; width: 100%;">{cols_html}</div>
    </div>
    {footer_html}
  </div>
""")

            elif layout == 1:
                p = prod_chunk[0]
                sku = clean_sku(p.get('item_no'), p.get('name') or '')
                p_name = clean_name(p.get('name'))
                img_url = get_image_file_url(p.get('img'))
                specs_html = render_specs_html(p['_specs'], 1)

                html_parts.append(f"""
  <div class="page" style="padding: 40pt 50pt 35pt; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      {header_html}
      <div style="display: grid; grid-template-columns: 1.15fr 1.05fr; gap: 36pt; align-items: center; margin-top: 8pt;">
        <div style="height: 370pt; background: #ffffff; border: 1pt solid #e2e8f0; border-radius: 10pt; padding: 20pt; display: flex; align-items: center; justify-content: center; box-shadow: 0 10pt 25pt -8pt rgba(0,0,0,0.06);">
          <img src="{img_url}" alt="{p_name}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
        </div>
        <div>
          <div style="font-family: 'Oswald', sans-serif; font-size: 28pt; font-weight: 700; color: #b81d24; letter-spacing: 0.04em; line-height: 1; margin-bottom: 6pt;">{sku}</div>
          <h3 style="font-family: 'Oswald', sans-serif; font-size: 21pt; font-weight: 700; color: #0f172a; text-transform: uppercase; line-height: 1.15; margin-bottom: 10pt;">{p_name}</h3>
          <div style="height: 2pt; background: #b81d24; width: 60pt; margin-bottom: 14pt;"></div>
          <div style="background: #f8fafc; border: 1pt solid #e2e8f0; border-radius: 8pt; padding: 16pt; margin-bottom: 14pt;">
            {specs_html}
          </div>
          <div style="display: flex; gap: 10pt; flex-wrap: wrap;">
            <span style="background: #f1f5f9; color: #475569; font-size: 8.5pt; font-weight: 700; padding: 4pt 10pt; border-radius: 4pt; text-transform: uppercase;">Alto Tráfico Continuo</span>
            <span style="background: #fef2f2; color: #b81d24; font-size: 8.5pt; font-weight: 700; padding: 4pt 10pt; border-radius: 4pt; text-transform: uppercase;">Garantía Directa Oficial</span>
            <span style="background: #f1f5f9; color: #475569; font-size: 8.5pt; font-weight: 700; padding: 4pt 10pt; border-radius: 4pt; text-transform: uppercase;">Soporte en Repuestos</span>
          </div>
        </div>
      </div>
    </div>
    {footer_html}
  </div>
""")

    # CONTRAPORTADA
    html_parts.append(f"""
  <div class="page" style="background: #0b132b; color: #ffffff; padding: 60pt; display: flex; flex-direction: column; justify-content: space-between; text-align: center;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <img src="{logo_url}" alt="Fitness Life" style="height: 48pt; width: auto; filter: brightness(0) invert(1);">
      <img src="{realleader_logo_url}" alt="Realleader" style="height: 36pt; width: auto; filter: brightness(0) invert(1);">
    </div>
    <div style="max-width: 580pt; margin: 0 auto;">
      <h2 style="font-size: 38pt; line-height: 1.1; text-transform: uppercase; margin-bottom: 16pt;">
        EQUIPAMIENTO DE ALTO NIVEL PARA PROYECTOS DEPORTIVOS DE ALTO IMPACTO
      </h2>
      <div style="height: 3pt; width: 80pt; background: #e63946; margin: 0 auto 24pt;"></div>
      <p style="font-size: 13pt; line-height: 1.7; color: #cbd5e1; margin-bottom: 32pt;">
        Cotiza tu gimnasio completo, renueva tus líneas o solicita una asesoría de diseño 3D con nuestros especialistas comerciales y técnicos.
      </p>
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20pt; text-align: left; background: rgba(255,255,255,0.06); padding: 24pt; border-radius: 10pt; border: 1pt solid rgba(255,255,255,0.15);">
        <div>
          <div style="font-size: 9.5pt; color: #e63946; font-weight: 700; text-transform: uppercase; margin-bottom: 4pt;">Sede & Bodega Principal</div>
          <div style="font-size: 11pt; color: #ffffff; font-weight: 600; line-height: 1.4;">Cra 1 # 30-75 barrio Fátima<br/>Cali, Colombia</div>
        </div>
        <div>
          <div style="font-size: 9.5pt; color: #e63946; font-weight: 700; text-transform: uppercase; margin-bottom: 4pt;">Líneas Comerciales / WhatsApp</div>
          <div style="font-size: 11pt; color: #ffffff; font-weight: 600; line-height: 1.4;">+57 (312) 801-1838<br/>+57 (312) 719-9008</div>
        </div>
        <div>
          <div style="font-size: 9.5pt; color: #e63946; font-weight: 700; text-transform: uppercase; margin-bottom: 4pt;">Canales Digitales</div>
          <div style="font-size: 11pt; color: #ffffff; font-weight: 600; line-height: 1.4;">fitnesslifesas@gmail.com<br/>www.fitnesslifesas.com</div>
        </div>
      </div>
    </div>
    <div>
      <div class="red-bar"></div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1pt solid rgba(255,255,255,0.1); padding-top: 12pt; font-size: 9pt; color: #64748b; margin-bottom: 6pt;">
        <span>© 2026 FITNESS LIFE S.A.S &bull; TODOS LOS DERECHOS RESERVADOS</span>
        <span>DISTRIBUIDOR AUTORIZADO REALLEADER FITNESS &bull; IMPORTACIÓN DIRECTA</span>
      </div>
    </div>
  </div>
</body>
</html>
""")

    full_html = "".join(html_parts)
    out_html_path = os.path.join(BASE_DIR, 'catalogo_print_completo.html')
    with open(out_html_path, 'w', encoding='utf-8') as f:
        f.write(full_html)

    print(f"HTML generado: {out_html_path} ({len(full_html)} bytes, {total_pages} páginas totales).")
    return out_html_path, total_pages, stats

def compile_pdf(html_path):
    out_pdf_local = os.path.join(BASE_DIR, 'Catalogo_Fitness_Life_2026_Completo.pdf')
    out_pdf_downloads = os.path.expanduser('~/Downloads/Catalogo_Fitness_Life_2026_Completo.pdf')

    chrome_app = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
    cmd = [
        chrome_app,
        "--headless=new",
        "--disable-gpu",
        "--no-pdf-header-footer",
        f"--print-to-pdf={out_pdf_local}",
        f"file://{html_path}"
    ]

    print("Compilando PDF con Google Chrome...")
    res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    if res.returncode != 0:
        print("Error en Chrome:", res.stderr)
        return False

    if os.path.exists(out_pdf_local):
        size_mb = os.path.getsize(out_pdf_local) / (1024 * 1024)
        print(f"PDF compilado exitosamente: {out_pdf_local} ({size_mb:.2f} MB)")
        shutil.copyfile(out_pdf_local, out_pdf_downloads)
        print(f"Copiado a Descargas: {out_pdf_downloads}")
        return out_pdf_local
    return False

if __name__ == '__main__':
    html_path, total_pages, stats = build_catalog_html()
    compile_pdf(html_path)
