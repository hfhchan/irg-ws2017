<?

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
?>
<title>Import</title>
<?
$sources_cache   = new SourcesCache();

$rows = '00059	IDS Check: Unify to WS2017-00058 (strong similarity)
00063	IDS Check: Unify to WS2017-00062 (strong similarity)
00178	IDS Check: Unify to WS2017-00177 (strong similarity)
00198	IDS Check: Unify to WS2017-00197 (strong similarity)
00269	IDS Check: Unify to WS2017-00268 (strong similarity)
00285	IDS Check: Unify to WS2017-00281 (strong similarity)
00338	IDS Check: Unify to WS2017-00336 (strong similarity)
00585	IDS Check: Unify to WS2017-00571 (strong similarity)
00606	IDS Check: Unify to WS2017-00604 (strong similarity)
00654	IDS Check: Unify to WS2017-00653 (strong similarity)
00659	IDS Check: Unify to WS2017-00657 (strong similarity)
00667	IDS Check: Unify to WS2017-00664 (strong similarity)
00672	IDS Check: Unify to WS2017-00670 (strong similarity)
00681	IDS Check: Unify to WS2017-00678 (strong similarity)
00689	IDS Check: Unify to WS2017-00686 (strong similarity)
00690	IDS Check: Unify to WS2017-00685 (strong similarity)
00707	IDS Check: Unify to WS2017-00706 (strong similarity)
00708	IDS Check: Unify to WS2017-00705 (strong similarity)
00712	IDS Check: Unify to WS2017-00709 (strong similarity)
00728	IDS Check: Unify to WS2017-00725 (strong similarity)
00750	IDS Check: Unify to WS2017-00746 (strong similarity)
00762	IDS Check: Unify to WS2017-00758 (strong similarity)
00781	IDS Check: Unify to WS2017-00779 (strong similarity)
00790	IDS Check: Unify to WS2017-00788 (strong similarity)
00885	IDS Check: Unify to WS2017-00882 (strong similarity)
00887	IDS Check: Unify to WS2017-00886 (strong similarity)
00895	IDS Check: Unify to WS2017-00893 (strong similarity)
00922	IDS Check: Unify to WS2017-00921 (strong similarity)
00948	IDS Check: Unify to WS2017-00946 (strong similarity)
00964	IDS Check: Unify to WS2017-00963 (strong similarity)
01053	IDS Check: Unify to WS2017-01049 (strong similarity)
01058	IDS Check: Unify to WS2017-01055 (strong similarity)
01067	IDS Check: Unify to WS2017-01065 (strong similarity)
01074	IDS Check: Unify to WS2017-01069 (strong similarity)
01079	IDS Check: Unify to WS2017-01078 (strong similarity)
01085	IDS Check: Unify to WS2017-01084 (strong similarity)
01092	IDS Check: Unify to WS2017-01091 (strong similarity)
01123	IDS Check: Unify to WS2017-01041 (strong similarity)
01125	IDS Check: Unify to WS2017-01121 (strong similarity)
01161	IDS Check: Unify to WS2017-01160 (strong similarity)
01163	IDS Check: Unify to WS2017-01162 (strong similarity)
01167	IDS Check: Unify to WS2017-01166 (strong similarity)
01180	IDS Check: Unify to WS2017-01179 (strong similarity)
01268	IDS Check: Unify to WS2017-01267 (strong similarity)
01271	IDS Check: Unify to WS2017-01264 (strong similarity)
01283	IDS Check: Unify to WS2017-01282 (strong similarity)
01362	IDS Check: Unify to WS2017-01361 (strong similarity)
01379	IDS Check: Unify to WS2017-01379 (strong similarity)
01392	IDS Check: Unify to WS2017-01391 (strong similarity)
01422	IDS Check: Unify to WS2017-01417 (strong similarity)
01598	IDS Check: Unify to WS2017-01597 (strong similarity)
01673	IDS Check: Unify to WS2017-01672 (strong similarity)
01705	IDS Check: Unify to WS2017-01703 (strong similarity)
01755	IDS Check: Unify to WS2017-01752 (strong similarity)
01773	IDS Check: Unify to WS2017-01769 (strong similarity)
01852	IDS Check: Unify to WS2017-00185 (strong similarity)
01888	IDS Check: Unify to WS2017-01886 (strong similarity)
01923	IDS Check: Unify to WS2017-01920 (strong similarity)
01925	IDS Check: Unify to WS2017-01922 (strong similarity)
02007	IDS Check: Unify to WS2017-02004 (strong similarity)
02056	IDS Check: Unify to WS2017-02042 (strong similarity)
02108	IDS Check: Unify to WS2017-02107 (strong similarity)
02272	IDS Check: Unify to WS2017-02270 (strong similarity)
02319	IDS Check: Unify to WS2017-02317 (strong similarity)
02376	IDS Check: Unify to WS2017-02374 (strong similarity)
02584	IDS Check: Unify to WS2017-02545 (strong similarity)
02697	IDS Check: Unify to WS2017-02696 (strong similarity)
02718	IDS Check: Unify to WS2017-02717 (strong similarity)
02734	IDS Check: Unify to WS2017-02731 (strong similarity)
02889	IDS Check: Unify to WS2017-02887 (strong similarity)
02920	IDS Check: Unify to WS2017-02914 (strong similarity)
02927	IDS Check: Unify to WS2017-00133 (strong similarity)
02937	IDS Check: Unify to WS2017-02936 (strong similarity)
02945	IDS Check: Unify to WS2017-02943 (strong similarity)
02973	IDS Check: Unify to WS2017-02972 (strong similarity)
02990	IDS Check: Unify to WS2017-02988 (strong similarity)
02993	IDS Check: Unify to WS2017-02991 (strong similarity)
03005	IDS Check: Unify to WS2017-03003 (strong similarity)
03010	IDS Check: Unify to WS2017-03008 (strong similarity)
03041	IDS Check: Unify to WS2017-03038 (strong similarity)
03054	IDS Check: Unify to WS2017-00002 (strong similarity)
03075	IDS Check: Unify to WS2017-03074 (strong similarity)
03116	IDS Check: Unify to WS2017-03098 (strong similarity)
03129	IDS Check: Unify to WS2017-03126 (strong similarity)
03329	IDS Check: Unify to WS2017-03326 (strong similarity)
03337	IDS Check: Unify to WS2017-03336 (strong similarity)
03364	IDS Check: Unify to WS2017-03362 (strong similarity)
03374	IDS Check: Unify to WS2017-03373 (strong similarity)
03480	IDS Check: Unify to WS2017-03478 (strong similarity)
03504	IDS Check: Unify to WS2017-03503 (strong similarity)
03684	IDS Check: Unify to WS2017-03680 (strong similarity)
03772	IDS Check: Unify to WS2017-03758 (strong similarity)
03874	IDS Check: Unify to WS2017-03869 (strong similarity)
04046	IDS Check: Unify to WS2017-04045 (strong similarity)
04121	IDS Check: Unify to WS2017-04120 (strong similarity)
04130	IDS Check: Unify to WS2017-04128 (strong similarity)
04187	IDS Check: Unify to WS2017-04177 (strong similarity)
04191	IDS Check: Unify to WS2017-04189 (strong similarity)
04209	IDS Check: Unify to WS2017-04207 (strong similarity)
04211	IDS Check: Unify to WS2017-03825 (strong similarity)
04218	IDS Check: Unify to WS2017-00067 (strong similarity)
04223	IDS Check: Unify to WS2017-04221 (strong similarity)
04283	IDS Check: Unify to WS2017-04282 (strong similarity)
04284	IDS Check: Unify to WS2017-04283 (strong similarity)
04284	IDS Check: Unify to WS2017-04282 (strong similarity)
04468	IDS Check: Unify to WS2017-04467 (strong similarity)
04500	IDS Check: Unify to WS2017-04499 (strong similarity)
04552	IDS Check: Unify to WS2017-04550 (strong similarity)
04553	IDS Check: Unify to WS2017-04551 (strong similarity)
04566	IDS Check: Unify to WS2017-04565 (strong similarity)
04573	IDS Check: Unify to WS2017-04572 (strong similarity)
04788	IDS Check: Unify to WS2017-04787 (strong similarity)
04915	IDS Check: Unify to WS2017-04914 (strong similarity)
04948	IDS Check: Unify to WS2017-04947 (strong similarity)
00094	IDS Check: Unify to U+2D37A (strong similarity)
00669	IDS Check: Unify to WS2015-00696 (strong similarity)
00797	IDS Check: Unify to WS2015-00785 (strong similarity)
00890	IDS Check: Unify to U+2BC1B (strong similarity)
01381	IDS Check: Unify to U+2BF0A (strong similarity)
01666	IDS Check: Unify to U+673C (strong similarity)
01731	IDS Check: Unify to U+2C0D2 (strong similarity)
02599	IDS Check: Unify to U+2C363 (strong similarity)
02607	IDS Check: Unify to U+2C36B (strong similarity)
02903	IDS Check: Unify to WS2015-02784 (strong similarity)
03296	IDS Check: Unify to U+2E15E (strong similarity)
03650	IDS Check: Unify to U+2C722 (strong similarity)
04910	IDS Check: Unify to U+9DE5 (strong similarity)
02241	IDS Check: Unify to WS2015-02143 (strong similarity)
04427	IDS Check: Unify to U+2B496 (strong similarity)
00340	IDS Check: Unify to WS2015-00498 (strong similarity)
00748	IDS Check: Unify to WS2015-00741 (strong similarity)
00751	IDS Check: Unify to U+2D3CA (strong similarity)
00773	IDS Check: Unify to WS2015-00770 (strong similarity)
00838	IDS Check: Unify to U+27113 (strong similarity)
01273	IDS Check: Unify to WS2015-02519 (strong similarity)
01316	IDS Check: Unify to U+227AF (strong similarity)
01369	IDS Check: Unify to WS2015-01318 (strong similarity)
01404	IDS Check: Unify to U+2AB67 (strong similarity)
01492	IDS Check: Unify to U+22DCE (strong similarity)
01577	IDS Check: Unify to U+2D979 (strong similarity)
01638	IDS Check: Unify to WS2015-01616 (strong similarity)
01644	IDS Check: Unify to U+232C5 (strong similarity)
01700	IDS Check: Unify to U+2DA74 (strong similarity)
01713	IDS Check: Unify to WS2015-01702 (strong similarity)
01741	IDS Check: Unify to WS2015-01718 (strong similarity)
01785	IDS Check: Unify to U+3BF6 (strong similarity)
01842	IDS Check: Unify to U+2D6A7 (strong similarity)
02019	IDS Check: Unify to U+2AD99 (strong similarity)
02041	IDS Check: Unify to WS2015-02057 (strong similarity)
02281	IDS Check: Unify to U+241A6 (strong similarity)
02773	IDS Check: Unify to U+2DF10 (strong similarity)
03048	IDS Check: Unify to U+2E05A (strong similarity)
03085	IDS Check: Unify to U+2E086 (strong similarity)
03265	IDS Check: Unify to WS2015-03086 (strong similarity)
03409	IDS Check: Unify to WS2015-03201 (strong similarity)
03418	IDS Check: Unify to U+2C5CA (strong similarity)
03621	IDS Check: Unify to U+2E0AE (strong similarity)
03673	IDS Check: Unify to U+833E (strong similarity)
03866	IDS Check: Unify to U+2C810 (strong similarity)
03936	IDS Check: Unify to WS2015-03783 (strong similarity)
03937	IDS Check: Unify to WS2015-03786 (strong similarity)
04027	IDS Check: Unify to U+2E640 (strong similarity)
04088	IDS Check: Unify to WS2015-04009 (strong similarity)
04089	IDS Check: Unify to U+2E680 (strong similarity)
04204	IDS Check: Unify to U+2A60F (strong similarity)
04208	IDS Check: Unify to U+2E720 (strong similarity)
04431	IDS Check: Unify to U+2E877 (strong similarity)
04435	IDS Check: Unify to U+28A7C (strong similarity)
04571	IDS Check: Unify to WS2015-04636 (strong similarity)
04760	IDS Check: Unify to U+2EA63 (strong similarity)
04957	IDS Check: Unify to U+2EB9C (strong similarity)
04964	IDS Check: Unify to U+2DF78 (strong similarity)
00381	IDS Check: Unify to U+356D (strong similarity)
01024	IDS Check: Unify to U+663C (strong similarity)
01147	IDS Check: Unify to U+2030A (strong similarity)
01262	IDS Check: Unify to U+5FCB (strong similarity)
01473	IDS Check: Unify to U+225E9 (strong similarity)
02752	IDS Check: Unify to U+24CF0 (strong similarity)
03070	IDS Check: Unify to WS2015-02933 (strong similarity)
03160	IDS Check: Unify to U+2585E (strong similarity)
03455	IDS Check: Unify to U+7E92 (strong similarity)
00001	IDS Check: Unify to U+2000A (strong similarity)
00380	IDS Check: Unify to U+20BC7 (strong similarity)
01109	IDS Check: Unify to U+21E8E (strong similarity)
01538	IDS Check: Unify to U+2D910 (strong similarity)
02190	IDS Check: Unify to U+2C25B (strong similarity)
02306	IDS Check: Unify to WS2015-02188 (strong similarity)
02326	IDS Check: Unify to U+2DD3C (strong similarity)
02503	IDS Check: Unify to U+3E48 (strong similarity)
02762	IDS Check: Unify to U+2DF06 (strong similarity)
03855	IDS Check: Unify to U+272B5 (strong similarity)
04058	IDS Check: Unify to U+2C920 (strong similarity)
04606	IDS Check: Unify to U+49F1 (strong similarity)
04875	IDS Check: Unify to U+2CD8A (strong similarity)
00667	IDS Check: Unify to U+2BB5B (strong similarity)
01318	IDS Check: Unify to U+2F8A4 (strong similarity)
01531	IDS Check: Unify to U+3A9C (strong similarity)
02182	IDS Check: Unify to U+2ADEB (strong similarity)
02728	IDS Check: Unify to U+2F936 (strong similarity)
03436	IDS Check: Unify to U+2F96E (strong similarity)
00664	IDS Check: Unify to U+2BB5B (strong similarity)
01549	IDS Check: Unify to WS2015-01522 (strong similarity)
01698	IDS Check: Unify to U+2DA84 (strong similarity)
03627	IDS Check: Unify to U+2C712 (strong similarity)
04071	IDS Check: Unify to WS2015-03975 (strong similarity)
04167	IDS Check: Unify to U+2E6E5 (strong similarity)
03689	IDS Check: Unify to U+26C25	 (moderate similarity)
02969	IDS Check: Unify to U+7847 (moderate similarity)
02132	IDS Check: Unify to WS2015-02097 (moderate similarity)
02203	IDS Check: Unify to U+2405F (moderate similarity)
02350	IDS Check: Unify to U+9FBD (moderate similarity)
03097	IDS Check: Unify to U+2E08A (moderate similarity)
03932	IDS Check: Unify to U+2763C (moderate similarity)
04583	IDS Check: Unify to U+28E94 (moderate similarity)
04625	IDS Check: Unify to WS2015-04729 (moderate similarity)
00383	IDS Check: Unify to U+20BD0	(moderate similarity)
00424	IDS Check: Unify to U+3582 (moderate similarity)
02448	IDS Check: Unify to U+2C2E9 (moderate similarity)
00576	IDS Check: Unify to U+2BB01 (moderate similarity)
00673	IDS Check: Unify to U+21D0D (moderate similarity)
01231	IDS Check: Unify to U+22466 (moderate similarity)
03502	IDS Check: Unify to U+262A8 (moderate similarity)
04826	IDS Check: Unify to U+4C05 (moderate similarity)
04912	IDS Check: Unify to U+9E01 (moderate similarity)
01466	IDS check: Unify to WS2015-01443 (moderate similarity)
00657	IDS Check: Weak Similarity to U+5732
00875	IDS Check: Weak Similarity to U+216AB
00875	IDS Check: Weak Similarity to U+216AD
00893	IDS Check: Weak Similarity to U+2A968
01284	IDS Check: Weak Similarity to U+3905
01661	IDS Check: Weak Similarity to U+2DA48
01892	IDS Check: Weak Similarity to U+6C6A
01892	IDS Check: Weak Similarity to U+2DBFA
02270	IDS Check: Weak Similarity to U+3DA5
02587	IDS Check: Weak Similarity to U+248FD
02587	IDS Check: Weak Similarity to U+2DE49
02833	IDS Check: Weak Similarity to U+21603
02833	IDS Check: Weak Similarity to U+76C7
02943	IDS Check: Weak Similarity to U+2E00B
03345	IDS Check: Weak Similarity to U+7C78
03658	IDS Check: Weak Similarity to U+26B30
03658	IDS Check: Weak Similarity to U+26B2C
03845	IDS Check: Weak Similarity to U+27257
03973	IDS Check: Weak Similarity to U+277EA
04037	IDS Check: Weak Similarity to U+8BAF
04732	IDS Check: Weak Similarity to U+29691
01894	IDS Check: Weak Similarity to U+2DC01
02269	IDS Check: Weak Similarity to U+7076
03231	IDS Check: Weak Similarity to U+25A0A
03415	IDS Check: Weak Similarity to U+25FBB
03662	IDS Check: Weak Similarity to U+26C78
04989	IDS Check: Weak Similarity to U+2A6D0
00066	IDS Check: Weak Similarity to U+20AF7
00066	IDS Check: Weak Similarity to U+21BEE
00066	IDS Check: Weak Similarity to U+21BE4
00128	IDS Check: Weak Similarity to U+2E280
01572	IDS Check: Weak Similarity to U+26968
02281	IDS Check: Weak Similarity to U+2AE1B
03574	IDS Check: Weak Similarity to U+808A
00031	IDS Check: Weak Similarity to U+51B3
00032	IDS Check: Weak Similarity to U+2B94B
00033	IDS Check: Weak Similarity to U+34CD
00034	IDS Check: Weak Similarity to U+34CA
00040	IDS Check: Weak Similarity to U+205F3
00354	IDS Check: Weak Similarity to U+2A824
00383	IDS Check: Weak Similarity to U+20CBC
01042	IDS Check: Weak Similarity to U+2D088
01262	IDS Check: Weak Similarity to U+225AD
01653	IDS Check: Weak Similarity to U+80FF
01876	IDS Check: Weak Similarity to U+6C3D
01876	IDS Check: Weak Similarity to U+6C46
02273	IDS Check: Weak Similarity to U+2C275
03519	IDS Check: Weak Similarity to U+2D08E
04000	IDS Check: Weak Similarity to U+8A0D
00030	IDS Check: Weak Similarity to U+7528
00227	IDS Check: Weak Similarity to U+2944E
00895	IDS Check: Weak Similarity to U+2A968
01395	IDS Check: Weak Similarity to U+22AAA
01439	IDS Check: Weak Similarity to U+63EC
01439	IDS Check: Weak Similarity to U+22B55
01735	IDS Check: Weak Similarity to U+234DC
01889	IDS Check: Weak Similarity to U+3CBD
02945	IDS Check: Weak Similarity to U+2E00B
03358	IDS Check: Weak Similarity to U+2B0B5
03359	IDS Check: Weak Similarity to U+2E1C7
03434	IDS Check: Weak Similarity to U+2603A
03502	IDS Check: Weak Similarity to U+26295
03616	IDS Check: Weak Similarity to U+573C
03999	IDS Check: Weak Similarity to U+8A10
04039	IDS Check: Weak Similarity to U+8BAE
04252	IDS Check: Weak Similarity to U+8EDA
04252	IDS Check: Weak Similarity to U+8ED1
00659	IDS Check: Weak Similarity to U+5732
00852	IDS Check: Weak Similarity to U+230D9
01318	IDS Check: Weak Similarity to U+226D4
01481	IDS Check: Weak Similarity to U+22D87
02267	IDS Check: Weak Similarity to U+2C27A
02272	IDS Check: Weak Similarity to U+3DA5
02728	IDS Check: Weak Similarity to U+753E
03436	IDS Check: Weak Similarity to U+7DC7
04145	IDS Check: Weak Similarity to U+27FF8
04802	IDS Check: Weak Similarity to U+29A50
00744	IDS Check: Weak Similarity to U+2136E
01549	IDS Check: Weak Similarity to U+230A2
02890	IDS Check: Weak Similarity to U+25206
03453	IDS Check: Weak Similarity to U+26195
03627	IDS Check: Weak Similarity to U+26A0C
03949	IDS Check: Weak Similarity to U+276D5
04141	IDS Check: Weak Similarity to U+27FCA
04141	IDS Check: Weak Similarity to U+27FC4
04141	IDS Check: Weak Similarity to U+27FC1
04141	IDS Check: Weak Similarity to U+2E6C2';

$rows = explode("\n", $rows);
foreach ($rows as $row) {
	$row = explode("\t", trim($row));
	
	
	var_dump($row);
	//DBComments::save($row[0], 'UNIFICATION', $row[1], 6);
	echo '<br>';
}