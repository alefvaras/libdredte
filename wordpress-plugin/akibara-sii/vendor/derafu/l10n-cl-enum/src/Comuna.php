<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL Enum - Chilean Enum Repository.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\Enum;

use RuntimeException;

/**
 * Enum to represent the communes of Chile.
 */
enum Comuna: int
{
    case IQUIQUE = 1101;
    case CAMIÑA = 1102;
    case COLCHANE = 1103;
    case HUARA = 1104;
    case PICA = 1105;
    case POZO_ALMONTE = 1106;
    case ALTO_HOSPICIO = 1107;
    case CAMARONES = 1202;
    case PUTRE = 1301;
    case GENERAL_LAGOS = 1302;
    case ANTOFAGASTA = 2101;
    case MEJILLONES = 2102;
    case SIERRA_GORDA = 2103;
    case TALTAL = 2104;
    case MARIA_ELENA = 2105;
    case CALAMA = 2201;
    case OLLAGUE = 2202;
    case SAN_PEDRO_DE_ATACAMA = 2203;
    case TOCOPILLA = 2301;
    case COPIAPO = 3101;
    case CALDERA = 3102;
    case TIERRA_AMARILLA = 3103;
    case CHAÑARAL = 3201;
    case DIEGO_DE_ALMAGRO = 3202;
    case VALLENAR = 3301;
    case ALTO_DEL_CARMEN = 3302;
    case FREIRINA = 3303;
    case HUASCO = 3304;
    case LA_SERENA = 4101;
    case COQUIMBO = 4102;
    case ANDACOLLO = 4103;
    case LA_HIGUERA = 4104;
    case PAIHUANO = 4105;
    case VICUÑA = 4106;
    case ILLAPEL = 4201;
    case CANELA = 4202;
    case LOS_VILOS = 4203;
    case SALAMANCA = 4204;
    case OVALLE = 4301;
    case COMBARBALA = 4302;
    case MONTE_PATRIA = 4303;
    case PUNITAQUI = 4304;
    case RIO_HURTADO = 4305;
    case VALPARAISO = 5101;
    case CASABLANCA = 5102;
    case CONCON = 5103;
    case JUAN_FERNANDEZ = 5104;
    case PUCHUNCAVI = 5105;
    case QUILPUE = 5106;
    case QUINTERO = 5107;
    case VILLA_ALEMANA = 5108;
    case VIÑA_DEL_MAR = 5109;
    case ISLA_DE_PASCUA = 5201;
    case LOS_ANDES = 5301;
    case CALLE_LARGA = 5302;
    case RINCONADA = 5303;
    case SAN_ESTEBAN = 5304;
    case LA_LIGUA = 5401;
    case CABILDO = 5402;
    case PAPUDO = 5403;
    case PETORCA = 5404;
    case ZAPALLAR = 5405;
    case QUILLOTA = 5501;
    case LA_CALERA = 5502;
    case HIJUELAS = 5503;
    case LA_CRUZ = 5504;
    case LIMACHE = 5505;
    case NOGALES = 5506;
    case OLMUE = 5507;
    case SAN_ANTONIO = 5601;
    case ALGARROBO = 5602;
    case CARTAGENA = 5603;
    case EL_QUISCO = 5604;
    case EL_TABO = 5605;
    case SANTO_DOMINGO = 5606;
    case SAN_FELIPE = 5701;
    case CATEMU = 5702;
    case LLAY_LLAY = 5703;
    case PANQUEHUE = 5704;
    case PUTAENDO = 5705;
    case SANTA_MARIA = 5706;
    case RANCAGUA = 6101;
    case CODEGUA = 6102;
    case COINCO = 6103;
    case COLTAUCO = 6104;
    case DONIHUE = 6105;
    case GRANEROS = 6106;
    case LAS_CABRAS = 6107;
    case MACHALI = 6108;
    case MALLOA = 6109;
    case MOSTAZAL = 6110;
    case OLIVAR = 6111;
    case PEUMO = 6112;
    case PICHIDEGUA = 6113;
    case QUINTA_DE_TILCOCO = 6114;
    case RENGO = 6115;
    case REQUINOA = 6116;
    case SAN_VICENTE = 6117;
    case PICHILEMU = 6201;
    case LA_ESTRELLA = 6202;
    case LITUECHE = 6203;
    case MARCHIGUE = 6204;
    case NAVIDAD = 6205;
    case PAREDONES = 6206;
    case SAN_FERNANDO = 6301;
    case CHEPICA = 6302;
    case CHIMBARONGO = 6303;
    case LOLOL = 6304;
    case NANCAGUA = 6305;
    case PALMILLA = 6306;
    case PERALILLO = 6307;
    case PLACILLA = 6308;
    case PUMANQUE = 6309;
    case SANTA_CRUZ = 6310;
    case TALCA = 7101;
    case CONSTITUCION = 7102;
    case CUREPTO = 7103;
    case EMPEDRADO = 7104;
    case MAULE = 7105;
    case PELARCO = 7106;
    case PENCAHUE = 7107;
    case RIO_CLARO = 7108;
    case SAN_CLEMENTE = 7109;
    case SAN_RAFAEL = 7110;
    case CAUQUENES = 7201;
    case CHANCO = 7202;
    case PELLUHUE = 7203;
    case CURICO = 7301;
    case HUALAÑE = 7302;
    case LICANTEN = 7303;
    case MOLINA = 7304;
    case RAUCO = 7305;
    case ROMERAL = 7306;
    case SAGRADA_FAMILIA = 7307;
    case TENO = 7308;
    case VICUQUEN = 7309;
    case LINARES = 7401;
    case COLBUN = 7402;
    case LONGAVI = 7403;
    case PARRAL = 7404;
    case RETIRO = 7405;
    case SAN_JAVIER = 7406;
    case VILLA_ALEGRE = 7407;
    case YERBAS_BUENAS = 7408;
    case CONCEPCION = 8101;
    case CORONEL = 8102;
    case CHIGUAYANTE = 8103;
    case FLORIDA = 8104;
    case HUALQUI = 8105;
    case LOTA = 8106;
    case PENCO = 8107;
    case SAN_PEDRO_DE_LA_PAZ = 8108;
    case SANTA_JUANA = 8109;
    case TALCAHUANO = 8110;
    case TOME = 8111;
    case LEBU = 8201;
    case ARAUCO = 8202;
    case CAÑETE = 8203;
    case CONTULMO = 8204;
    case CURANILAHUE = 8205;
    case LOS_ALAMOS = 8206;
    case TIRUA = 8207;
    case HUALPEN = 8208;
    case LOS_ANGELES = 8301;
    case ANTUCO = 8302;
    case CABRERO = 8303;
    case LAJA = 8304;
    case MULCHEN = 8305;
    case NACIMIENTO = 8306;
    case NEGRETE = 8307;
    case QUILACO = 8308;
    case QUILLECO = 8309;
    case SAN_ROSENDO = 8310;
    case SANTA_BARBARA = 8311;
    case TUCAPEL = 8312;
    case YUMBEL = 8313;
    case ALTO_BIO_BIO = 8314;
    case CHILLAN = 16101;
    case BULNES = 16102;
    case COBQUECURA = 16202;
    case COELEMU = 16203;
    case COIHUECO = 16302;
    case CHILLAN_VIEJO = 16103;
    case EL_CARMEN = 16104;
    case NINHUE = 16204;
    case NIQUEN = 16303;
    case PEMUCO = 16105;
    case PINTO = 16106;
    case PORTEZUELO = 16205;
    case QUILLON = 16107;
    case QUIRIHUE = 16201;
    case RANQUIL = 16206;
    case SAN_CARLOS = 16301;
    case SAN_FABIAN = 16304;
    case SAN_IGNACIO = 16108;
    case SAN_NICOLAS = 16305;
    case TREGUACO = 16207;
    case YUNGAY = 16109;
    case TEMUCO = 9101;
    case CARAHUE = 9102;
    case CUNCO = 9103;
    case CURARREHUE = 9104;
    case FREIRE = 9105;
    case GALVARINO = 9106;
    case GORBEA = 9107;
    case LAUTARO = 9108;
    case LONCOCHE = 9109;
    case MELIPEUCO = 9110;
    case NUEVA_IMPERIAL = 9111;
    case PADRE_LAS_CASAS = 9112;
    case PERQUENCO = 9113;
    case PITRUFQUEN = 9114;
    case PUCON = 9115;
    case SAAVEDRA = 9116;
    case TEODORO_SCHMIDT = 9117;
    case TOLTEN = 9118;
    case VILCUN = 9119;
    case VILLARRICA = 9120;
    case CHOLCHOL = 9121;
    case ANGOL = 9201;
    case COLLIPULLI = 9202;
    case CURACAUTIN = 9203;
    case ERCILLA = 9204;
    case LONQUIMAY = 9205;
    case LOS_SAUCES = 9206;
    case LUMACO = 9207;
    case PUREN = 9208;
    case RENAICO = 9209;
    case TRAIGUEN = 9210;
    case VICTORIA = 9211;
    case PUERTO_MONTT = 10101;
    case CALBUCO = 10102;
    case COCHAMO = 10103;
    case FRESIA = 10104;
    case FRUTILLAR = 10105;
    case LOS_MUERMO = 10106;
    case LLANQUIHUE = 10107;
    case MAULLIN = 10108;
    case PUERTO_VARAS = 10109;
    case CASTRO = 10201;
    case ANCUD = 10202;
    case CHONCHI = 10203;
    case CURACO_DE_VELEZ = 10204;
    case DALCAHUE = 10205;
    case PUQUELDON = 10206;
    case QUEILEN = 10207;
    case QUELLON = 10208;
    case QUEMCHI = 10209;
    case QUINCHAO = 10210;
    case OSORNO = 10301;
    case PUERTO_OCTAY = 10302;
    case PURRANQUE = 10303;
    case PUYEHUE = 10304;
    case RIO_NEGRO = 10305;
    case SAN_JUAN_DE_LA_COSTA = 10306;
    case SAN_PABLO = 10307;
    case CHAITE = 10401;
    case FUTALEUFU = 10402;
    case HUALAIHUE = 10403;
    case PALENA = 10404;
    case VALDIVIA = 10501;
    case CORRAL = 10502;
    case FUTRONO = 10503;
    case LA_UNION = 10504;
    case LAGO_RANCO = 10505;
    case LANCO = 10506;
    case LOS_LAGOS = 10507;
    case MAFIL = 10508;
    case MARIQUINA = 10509;
    case PAILLACO = 10510;
    case PANGUIPULLI = 10511;
    case RIO_BUENO = 10512;
    case COIHAIQUE = 11101;
    case LAGO_VERDE = 11102;
    case AISEN = 11201;
    case CISNES = 11202;
    case GUAITECAS = 11203;
    case COCHRANE = 11301;
    case OHIGGINS = 11302;
    case TORTEL = 11303;
    case CHILE_CHICO = 11401;
    case RIO_IBANEZ = 11402;
    case PUNTA_ARENAS = 12101;
    case LAGUNA_BLANCA = 12102;
    case RIO_VERDE = 12103;
    case SAN_GREGORIO = 12104;
    case CABO_DE_HORNO = 12201;
    case ANTARTICA = 12202;
    case PORVENIR = 12301;
    case PRIMAVERA = 12302;
    case TIMAUKEL = 12303;
    case NATALES = 12401;
    case TORRES_DEL_PAINE = 12402;
    case SANTIAGO = 13101;
    case CERRILLOS = 13102;
    case CERRO_NAVIA = 13103;
    case CONCHALI = 13104;
    case EL_BOSQUE = 13105;
    case ESTACION_CENTRAL = 13106;
    case HUECHURABA = 13107;
    case INDEPENDENCIA = 13108;
    case LA_CISTERNA = 13109;
    case LA_FLORIDA = 13110;
    case LA_GRANJA = 13111;
    case LA_PINTANA = 13112;
    case LA_REINA = 13113;
    case LAS_CONDES = 13114;
    case LO_BARNECHEA = 13115;
    case LO_ESPEJO = 13116;
    case LO_PRADO = 13117;
    case MACUL = 13118;
    case MAIPU = 13119;
    case NUNOA = 13120;
    case PEDRO_AGUIRRE_CERDA = 13121;
    case PENALOLEN = 13122;
    case PROVIDENCIA = 13123;
    case PUDAHUEL = 13124;
    case QUILICURA = 13125;
    case QUINTA_NORMAL = 13126;
    case RECOLETA = 13127;
    case RENCA = 13128;
    case SAN_JOAQUIN = 13129;
    case SAN_MIGUEL = 13130;
    case SAN_RAMON = 13131;
    case VITACURA = 13132;
    case PUENTE_ALTO = 13201;
    case PIRQUE = 13202;
    case SAN_JOSE_DE_MAIPO = 13203;
    case COLINA = 13301;
    case LAMPA = 13302;
    case TILTIL = 13303;
    case SAN_BERNARDO = 13401;
    case BUIN = 13402;
    case CALERA_DE_TANGO = 13403;
    case PAINE = 13404;
    case MELIPILLA = 13501;
    case ALHUE = 13502;
    case CURACAVI = 13503;
    case MARIA_PINTO = 13504;
    case SAN_PEDRO = 13505;
    case TALAGANTE = 13601;
    case EL_MONTE = 13602;
    case ISLA_DE_MAIPO = 13603;
    case PADRE_HURTADO = 13604;
    case PENAFLOR = 13605;
    case ARICA = 15101;

    private const GLOSAS = [
        self::IQUIQUE->value => 'Iquique',
        self::CAMIÑA->value => 'Camiña',
        self::COLCHANE->value => 'Colchane',
        self::HUARA->value => 'Huara',
        self::PICA->value => 'Pica',
        self::POZO_ALMONTE->value => 'Pozo Almonte',
        self::ALTO_HOSPICIO->value => 'Alto Hospicio',
        self::CAMARONES->value => 'Camarones',
        self::PUTRE->value => 'Putre',
        self::GENERAL_LAGOS->value => 'General Lagos',
        self::ANTOFAGASTA->value => 'Antofagasta',
        self::MEJILLONES->value => 'Mejillones',
        self::SIERRA_GORDA->value => 'Sierra Gorda',
        self::TALTAL->value => 'Taltal',
        self::MARIA_ELENA->value => 'María Elena',
        self::CALAMA->value => 'Calama',
        self::OLLAGUE->value => 'Ollagüe',
        self::SAN_PEDRO_DE_ATACAMA->value => 'San Pedro de Atacama',
        self::TOCOPILLA->value => 'Tocopilla',
        self::COPIAPO->value => 'Copiapó',
        self::CALDERA->value => 'Caldera',
        self::TIERRA_AMARILLA->value => 'Tierra Amarilla',
        self::CHAÑARAL->value => 'Chañaral',
        self::DIEGO_DE_ALMAGRO->value => 'Diego de Almagro',
        self::VALLENAR->value => 'Vallenar',
        self::ALTO_DEL_CARMEN->value => 'Alto del Carmen',
        self::FREIRINA->value => 'Freirina',
        self::HUASCO->value => 'Huasco',
        self::LA_SERENA->value => 'La Serena',
        self::COQUIMBO->value => 'Coquimbo',
        self::ANDACOLLO->value => 'Andacollo',
        self::LA_HIGUERA->value => 'La Higuera',
        self::PAIHUANO->value => 'Paihuano',
        self::VICUÑA->value => 'Vicuña',
        self::ILLAPEL->value => 'Illapel',
        self::CANELA->value => 'Canela',
        self::LOS_VILOS->value => 'Los Vilos',
        self::SALAMANCA->value => 'Salamanca',
        self::OVALLE->value => 'Ovalle',
        self::COMBARBALA->value => 'Combarbalá',
        self::MONTE_PATRIA->value => 'Monte Patria',
        self::PUNITAQUI->value => 'Punitaqui',
        self::RIO_HURTADO->value => 'Río Hurtado',
        self::VALPARAISO->value => 'Valparaíso',
        self::CASABLANCA->value => 'Casablanca',
        self::CONCON->value => 'Concón',
        self::JUAN_FERNANDEZ->value => 'Juan Fernández',
        self::PUCHUNCAVI->value => 'Puchuncaví',
        self::QUILPUE->value => 'Quilpué',
        self::QUINTERO->value => 'Quintero',
        self::VILLA_ALEMANA->value => 'Villa Alemana',
        self::VIÑA_DEL_MAR->value => 'Viña del Mar',
        self::ISLA_DE_PASCUA->value => 'Isla de Pascua',
        self::LOS_ANDES->value => 'Los Andes',
        self::CALLE_LARGA->value => 'Calle Larga',
        self::RINCONADA->value => 'Rinconada',
        self::SAN_ESTEBAN->value => 'San Esteban',
        self::LA_LIGUA->value => 'La Ligua',
        self::CABILDO->value => 'Cabildo',
        self::PAPUDO->value => 'Papudo',
        self::PETORCA->value => 'Petorca',
        self::ZAPALLAR->value => 'Zapallar',
        self::QUILLOTA->value => 'Quillota',
        self::LA_CALERA->value => 'La Calera',
        self::HIJUELAS->value => 'Hijuelas',
        self::LA_CRUZ->value => 'La Cruz',
        self::LIMACHE->value => 'Limache',
        self::NOGALES->value => 'Nogales',
        self::OLMUE->value => 'Olmué',
        self::SAN_ANTONIO->value => 'San Antonio',
        self::ALGARROBO->value => 'Algarrobo',
        self::CARTAGENA->value => 'Cartagena',
        self::EL_QUISCO->value => 'El Quisco',
        self::EL_TABO->value => 'El Tabo',
        self::SANTO_DOMINGO->value => 'Santo Domingo',
        self::SAN_FELIPE->value => 'San Felipe',
        self::CATEMU->value => 'Catemu',
        self::LLAY_LLAY->value => 'Llay Llay',
        self::PANQUEHUE->value => 'Panquehue',
        self::PUTAENDO->value => 'Putaendo',
        self::SANTA_MARIA->value => 'Santa María',
        self::RANCAGUA->value => 'Rancagua',
        self::CODEGUA->value => 'Codegua',
        self::COINCO->value => 'Coinco',
        self::COLTAUCO->value => 'Coltauco',
        self::DONIHUE->value => 'Doñihue',
        self::GRANEROS->value => 'Graneros',
        self::LAS_CABRAS->value => 'Las Cabras',
        self::MACHALI->value => 'Machalí',
        self::MALLOA->value => 'Malloa',
        self::MOSTAZAL->value => 'Mostazal',
        self::OLIVAR->value => 'Olivar',
        self::PEUMO->value => 'Peumo',
        self::PICHIDEGUA->value => 'Pichidegua',
        self::QUINTA_DE_TILCOCO->value => 'Quinta de Tilcoco',
        self::RENGO->value => 'Rengo',
        self::REQUINOA->value => 'Requínoa',
        self::SAN_VICENTE->value => 'San Vicente',
        self::PICHILEMU->value => 'Pichilemu',
        self::LA_ESTRELLA->value => 'La Estrella',
        self::LITUECHE->value => 'Litueche',
        self::MARCHIGUE->value => 'Marchigüe',
        self::NAVIDAD->value => 'Navidad',
        self::PAREDONES->value => 'Paredones',
        self::SAN_FERNANDO->value => 'San Fernando',
        self::CHEPICA->value => 'Chépica',
        self::CHIMBARONGO->value => 'Chimbarongo',
        self::LOLOL->value => 'Lolol',
        self::NANCAGUA->value => 'Nancagua',
        self::PALMILLA->value => 'Palmilla',
        self::PERALILLO->value => 'Peralillo',
        self::PLACILLA->value => 'Placilla',
        self::PUMANQUE->value => 'Pumanque',
        self::SANTA_CRUZ->value => 'Santa Cruz',
        self::TALCA->value => 'Talca',
        self::CONSTITUCION->value => 'Constitución',
        self::CUREPTO->value => 'Curepto',
        self::EMPEDRADO->value => 'Empedrado',
        self::MAULE->value => 'Maule',
        self::PELARCO->value => 'Pelarco',
        self::PENCAHUE->value => 'Pencahue',
        self::RIO_CLARO->value => 'Río Claro',
        self::SAN_CLEMENTE->value => 'San Clemente',
        self::SAN_RAFAEL->value => 'San Rafael',
        self::CAUQUENES->value => 'Cauquenes',
        self::CHANCO->value => 'Chanco',
        self::PELLUHUE->value => 'Pelluhue',
        self::CURICO->value => 'Curicó',
        self::HUALAÑE->value => 'Hualañé',
        self::LICANTEN->value => 'Licantén',
        self::MOLINA->value => 'Molina',
        self::RAUCO->value => 'Rauco',
        self::ROMERAL->value => 'Romeral',
        self::SAGRADA_FAMILIA->value => 'Sagrada Familia',
        self::TENO->value => 'Teno',
        self::VICUQUEN->value => 'Vichuquén',
        self::LINARES->value => 'Linares',
        self::COLBUN->value => 'Colbún',
        self::LONGAVI->value => 'Longaví',
        self::PARRAL->value => 'Parral',
        self::RETIRO->value => 'Retiro',
        self::SAN_JAVIER->value => 'San Javier',
        self::VILLA_ALEGRE->value => 'Villa Alegre',
        self::YERBAS_BUENAS->value => 'Yerbas Buenas',
        self::CONCEPCION->value => 'Concepción',
        self::CORONEL->value => 'Coronel',
        self::CHIGUAYANTE->value => 'Chiguayante',
        self::FLORIDA->value => 'Florida',
        self::HUALQUI->value => 'Hualqui',
        self::LOTA->value => 'Lota',
        self::PENCO->value => 'Penco',
        self::SAN_PEDRO_DE_LA_PAZ->value => 'San Pedro de La Paz',
        self::SANTA_JUANA->value => 'Santa Juana',
        self::TALCAHUANO->value => 'Talcahuano',
        self::TOME->value => 'Tomé',
        self::LEBU->value => 'Lebu',
        self::ARAUCO->value => 'Arauco',
        self::CAÑETE->value => 'Cañete',
        self::CONTULMO->value => 'Contulmo',
        self::CURANILAHUE->value => 'Curanilahue',
        self::LOS_ALAMOS->value => 'Los Álamos',
        self::TIRUA->value => 'Tirúa',
        self::HUALPEN->value => 'Hualpén',
        self::LOS_ANGELES->value => 'Los Ángeles',
        self::ANTUCO->value => 'Antuco',
        self::CABRERO->value => 'Cabrero',
        self::LAJA->value => 'Laja',
        self::MULCHEN->value => 'Mulchén',
        self::NACIMIENTO->value => 'Nacimiento',
        self::NEGRETE->value => 'Negrete',
        self::QUILACO->value => 'Quilaco',
        self::QUILLECO->value => 'Quilleco',
        self::SAN_ROSENDO->value => 'San Rosendo',
        self::SANTA_BARBARA->value => 'Santa Bárbara',
        self::TUCAPEL->value => 'Tucapel',
        self::YUMBEL->value => 'Yumbel',
        self::ALTO_BIO_BIO->value => 'Alto Bio Bio',
        self::CHILLAN->value => 'Chillán',
        self::BULNES->value => 'Bulnes',
        self::COBQUECURA->value => 'Cobquecura',
        self::COELEMU->value => 'Coelemu',
        self::COIHUECO->value => 'Coihueco',
        self::CHILLAN_VIEJO->value => 'Chillán Viejo',
        self::EL_CARMEN->value => 'El Carmen',
        self::NINHUE->value => 'Ninhue',
        self::NIQUEN->value => 'Ñiquén',
        self::PEMUCO->value => 'Pemuco',
        self::PINTO->value => 'Pinto',
        self::PORTEZUELO->value => 'Portezuelo',
        self::QUILLON->value => 'Quillón',
        self::QUIRIHUE->value => 'Quirihue',
        self::RANQUIL->value => 'Ránquil',
        self::SAN_CARLOS->value => 'San Carlos',
        self::SAN_FABIAN->value => 'San Fabián',
        self::SAN_IGNACIO->value => 'San Ignacio',
        self::SAN_NICOLAS->value => 'San Nicolás',
        self::TREGUACO->value => 'Treguaco',
        self::YUNGAY->value => 'Yungay',
        self::TEMUCO->value => 'Temuco',
        self::CARAHUE->value => 'Carahue',
        self::CUNCO->value => 'Cunco',
        self::CURARREHUE->value => 'Curarrehue',
        self::FREIRE->value => 'Freire',
        self::GALVARINO->value => 'Galvarino',
        self::GORBEA->value => 'Gorbea',
        self::LAUTARO->value => 'Lautaro',
        self::LONCOCHE->value => 'Loncoche',
        self::MELIPEUCO->value => 'Melipeuco',
        self::NUEVA_IMPERIAL->value => 'Nueva Imperial',
        self::PADRE_LAS_CASAS->value => 'Padre Las Casas',
        self::PERQUENCO->value => 'Perquenco',
        self::PITRUFQUEN->value => 'Pitrufquén',
        self::PUCON->value => 'Pucón',
        self::SAAVEDRA->value => 'Saavedra',
        self::TEODORO_SCHMIDT->value => 'Teodoro Schmidt',
        self::TOLTEN->value => 'Toltén',
        self::VILCUN->value => 'Vilcún',
        self::VILLARRICA->value => 'Villarrica',
        self::CHOLCHOL->value => 'Cholchol',
        self::ANGOL->value => 'Angol',
        self::COLLIPULLI->value => 'Collipulli',
        self::CURACAUTIN->value => 'Curacautín',
        self::ERCILLA->value => 'Ercilla',
        self::LONQUIMAY->value => 'Lonquimay',
        self::LOS_SAUCES->value => 'Los Sauces',
        self::LUMACO->value => 'Lumaco',
        self::PUREN->value => 'Purén',
        self::RENAICO->value => 'Renaico',
        self::TRAIGUEN->value => 'Traiguén',
        self::VICTORIA->value => 'Victoria',
        self::PUERTO_MONTT->value => 'Puerto Montt',
        self::CALBUCO->value => 'Calbuco',
        self::COCHAMO->value => 'Cochamó',
        self::FRESIA->value => 'Fresia',
        self::FRUTILLAR->value => 'Frutillar',
        self::LOS_MUERMO->value => 'Los Muermos',
        self::LLANQUIHUE->value => 'Llanquihue',
        self::MAULLIN->value => 'Maullín',
        self::PUERTO_VARAS->value => 'Puerto Varas',
        self::CASTRO->value => 'Castro',
        self::ANCUD->value => 'Ancud',
        self::CHONCHI->value => 'Chonchi',
        self::CURACO_DE_VELEZ->value => 'Curaco De Vélez',
        self::DALCAHUE->value => 'Dalcahue',
        self::PUQUELDON->value => 'Puqueldón',
        self::QUEILEN->value => 'Queilén',
        self::QUELLON->value => 'Quellón',
        self::QUEMCHI->value => 'Quemchi',
        self::QUINCHAO->value => 'Quinchao',
        self::OSORNO->value => 'Osorno',
        self::PUERTO_OCTAY->value => 'Puerto Octay',
        self::PURRANQUE->value => 'Purranque',
        self::PUYEHUE->value => 'Puyehue',
        self::RIO_NEGRO->value => 'Río Negro',
        self::SAN_JUAN_DE_LA_COSTA->value => 'San Juan De La Costa',
        self::SAN_PABLO->value => 'San Pablo',
        self::CHAITE->value => 'Chaitén',
        self::FUTALEUFU->value => 'Futaleufú',
        self::HUALAIHUE->value => 'Hualaihué',
        self::PALENA->value => 'Palena',
        self::VALDIVIA->value => 'Valdivia',
        self::CORRAL->value => 'Corral',
        self::FUTRONO->value => 'Futrono',
        self::LA_UNION->value => 'La Unión',
        self::LAGO_RANCO->value => 'Lago Ranco',
        self::LANCO->value => 'Lanco',
        self::LOS_LAGOS->value => 'Los Lagos',
        self::MAFIL->value => 'Máfil',
        self::MARIQUINA->value => 'Mariquina',
        self::PAILLACO->value => 'Paillaco',
        self::PANGUIPULLI->value => 'Panguipulli',
        self::RIO_BUENO->value => 'Río Bueno',
        self::COIHAIQUE->value => 'Coihaique',
        self::LAGO_VERDE->value => 'Lago Verde',
        self::AISEN->value => 'Aisén',
        self::CISNES->value => 'Cisnes',
        self::GUAITECAS->value => 'Guaitecas',
        self::COCHRANE->value => 'Cochrane',
        self::OHIGGINS->value => "O'Higgins",
        self::TORTEL->value => 'Tortel',
        self::CHILE_CHICO->value => 'Chile Chico',
        self::RIO_IBANEZ->value => 'Río Ibáñez',
        self::PUNTA_ARENAS->value => 'Punta Arenas',
        self::LAGUNA_BLANCA->value => 'Laguna Blanca',
        self::RIO_VERDE->value => 'Río Verde',
        self::SAN_GREGORIO->value => 'San Gregorio',
        self::CABO_DE_HORNO->value => 'Cabo De Horno',
        self::ANTARTICA->value => 'Antártica',
        self::PORVENIR->value => 'Porvenir',
        self::PRIMAVERA->value => 'Primavera',
        self::TIMAUKEL->value => 'Timaukel',
        self::NATALES->value => 'Natales',
        self::TORRES_DEL_PAINE->value => 'Torres Del Paine',
        self::SANTIAGO->value => 'Santiago',
        self::CERRILLOS->value => 'Cerrillos',
        self::CERRO_NAVIA->value => 'Cerro Navia',
        self::CONCHALI->value => 'Conchalí',
        self::EL_BOSQUE->value => 'El Bosque',
        self::ESTACION_CENTRAL->value => 'Estación Central',
        self::HUECHURABA->value => 'Huechuraba',
        self::INDEPENDENCIA->value => 'Independencia',
        self::LA_CISTERNA->value => 'La Cisterna',
        self::LA_FLORIDA->value => 'La Florida',
        self::LA_GRANJA->value => 'La Granja',
        self::LA_PINTANA->value => 'La Pintana',
        self::LA_REINA->value => 'La Reina',
        self::LAS_CONDES->value => 'Las Condes',
        self::LO_BARNECHEA->value => 'Lo Barnechea',
        self::LO_ESPEJO->value => 'Lo Espejo',
        self::LO_PRADO->value => 'Lo Prado',
        self::MACUL->value => 'Macul',
        self::MAIPU->value => 'Maipú',
        self::NUNOA->value => 'Ñuñoa',
        self::PEDRO_AGUIRRE_CERDA->value => 'Pedro Aguirre Cerda',
        self::PENALOLEN->value => 'Peñalolén',
        self::PROVIDENCIA->value => 'Providencia',
        self::PUDAHUEL->value => 'Pudahuel',
        self::QUILICURA->value => 'Quilicura',
        self::QUINTA_NORMAL->value => 'Quinta Normal',
        self::RECOLETA->value => 'Recoleta',
        self::RENCA->value => 'Renca',
        self::SAN_JOAQUIN->value => 'San Joaquín',
        self::SAN_MIGUEL->value => 'San Miguel',
        self::SAN_RAMON->value => 'San Ramón',
        self::VITACURA->value => 'Vitacura',
        self::PUENTE_ALTO->value => 'Puente Alto',
        self::PIRQUE->value => 'Pirque',
        self::SAN_JOSE_DE_MAIPO->value => 'San José De Maipo',
        self::COLINA->value => 'Colina',
        self::LAMPA->value => 'Lampa',
        self::TILTIL->value => 'Tiltil',
        self::SAN_BERNARDO->value => 'San Bernardo',
        self::BUIN->value => 'Buin',
        self::CALERA_DE_TANGO->value => 'Calera De Tango',
        self::PAINE->value => 'Paine',
        self::MELIPILLA->value => 'Melipilla',
        self::ALHUE->value => 'Alhué',
        self::CURACAVI->value => 'Curacaví',
        self::MARIA_PINTO->value => 'María Pinto',
        self::SAN_PEDRO->value => 'San Pedro',
        self::TALAGANTE->value => 'Talagante',
        self::EL_MONTE->value => 'El Monte',
        self::ISLA_DE_MAIPO->value => 'Isla de Maipo',
        self::PADRE_HURTADO->value => 'Padre Hurtado',
        self::PENAFLOR->value => 'Peñaflor',
        self::ARICA->value => 'Arica',
    ];

    /**
     * Returns the normalized code of the commune in 5 digits.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->value < 10000
            ? '0' . $this->value
            : (string) $this->value
        ;
    }

    /**
     * Returns the glosa associated with a commune.
     *
     * @return string
     */
    public function getGlosa(): string
    {
        return self::GLOSAS[$this->value];
    }

    /**
     * Returns the name of the commune.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->getGlosa();
    }

    /**
     * Returns the region to which the commune belongs.
     *
     * The first 2 digits of the normalized code of the commune
     * correspond to the normalized code of the region.
     *
     * @return Region
     */
    public function getRegion(): Region
    {
        $regionCode = (int) substr($this->getCodigo(), 0, 2);
        $region = Region::tryFrom($regionCode);

        if ($region === null) {
            throw new RuntimeException(sprintf(
                'Region not found for the commune %s.',
                $this->getNombre()
            ));
        }

        return $region;
    }

    /**
     * Returns the province to which the commune belongs.
     *
     * The first 3 digits of the normalized code of the commune
     * correspond to the normalized code of the province.
     *
     * @return Provincia
     */
    public function getProvincia(): Provincia
    {
        $provinciaCode = (int) substr($this->getCodigo(), 0, 3);
        $provincia = Provincia::tryFrom($provinciaCode);

        if ($provincia === null) {
            throw new RuntimeException(sprintf(
                'Province not found for the commune %s.',
                $this->getNombre()
            ));
        }

        return $provincia;
    }
}
