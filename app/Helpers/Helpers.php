<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Deals;
use Illuminate\Support\Carbon;

class Helpers
{
    public static function getJWTData($key = null)
    {
        $token = request()->header("authorization");
        $token = JWTAuth::getToken();
        $decoded = JWTAuth::getPayload($token)->toArray();

        if ($key) {
            return $decoded[$key];
        }

        return $decoded;
    }
    public static function getDurations($duration = null)
    {
        $durationData = [
            ["value" => "15", "text" => "15 min"],
            ["value" => "30", "text" => "30 min"],
            ["value" => "45", "text" => "45 min"],
            ["value" => "60", "text" => "1h"],
            ["value" => "75", "text" => "1h 15 min"],
            ["value" => "90", "text" => "1h 30 min"],
            ["value" => "105", "text" => "1h 45 min"],
            ["value" => "120", "text" => "2h"],
            ["value" => "135", "text" => "2h 15 min"],
            ["value" => "150", "text" => "2h 30 min"],
            ["value" => "165", "text" => "2h 45 min"],
            ["value" => "180", "text" => "3h"]
        ];

        if (empty($duration)) {
            return $durationData;
        }

        foreach ($durationData as $data) {
            if ($data["value"] == $duration) {
                return $data;
            }
        }
        
        return $duration;
    }

    public static function getTreatmentTypes($value = null)
    {
        $treatment_types = [
            [
                "label" => "Hair",
                "options" => [
                    ["value" => "1", "text" => "Japanese Straightening"],
                    ["value" => "2", "text" => "Haircuts and Hairdressing"],
                    ["value" => "3", "text" => "Hair Transplants"],
                    ["value" => "4", "text" => "Hair Loss Treatments - non surgical"],
                    ["value" => "5", "text" => "Hair Extensions"],
                    ["value" => "6", "text" => "Hair Consulting"],
                    ["value" => "7", "text" => "Hair Conditioning"],
                    ["value" => "8", "text" => "Hair Colouring and Highlights"],
                    ["value" => "9", "text" => "Brocato's Smoothing"],
                    ["value" => "10", "text" => "Brazilian Blow Dry Keratin"],
                    ["value" => "11", "text" => "Blow Dry"],
                    ["value" => "12", "text" => "Airlites"],
                    ["value" => "13", "text" => "Wedding Hair"],
                    ["value" => "14", "text" => "Straighteners"],
                    ["value" => "15", "text" => "Permanent Waves"]
                ]
            ],
            [
                "label" => "Body",
                "options" => [
                    ["value" => "16", "text" => "Multi Polar Radio Frequency Treatment"],
                    ["value" => "17", "text" => "Acoustic Wave Therapy"],
                    ["value" => "18", "text" => "Acupuncture"],
                    ["value" => "19", "text" => "Akasuri"],
                    ["value" => "20", "text" => "Arasys Toning & Inch Loss Treatment"],
                    ["value" => "21", "text" => "Backcials"],
                    ["value" => "22", "text" => "Bikini Facial"],
                    ["value" => "23", "text" => "Body Exfoliation Treatments"],
                    ["value" => "24", "text" => "Body Treatments"],
                    ["value" => "25", "text" => "Body Treatments - CACI"],
                    ["value" => "26", "text" => "Body Wraps"],
                    ["value" => "27", "text" => "Cellulite Treatments"],
                    ["value" => "28", "text" => "Colonic Hydrotherapy"],
                    ["value" => "29", "text" => "Colonic Therapy"],
                    ["value" => "30", "text" => "Cryolipolysis"],
                    ["value" => "31", "text" => "Cryotherapy"],
                    ["value" => "32", "text" => "Cupping"],
                    ["value" => "33", "text" => "Dry Floatation"],
                    ["value" => "34", "text" => "Electrotherapy"],
                    ["value" => "35", "text" => "Endermologie Lipo-Massage"],
                    ["value" => "36", "text" => "Endermotherapy"],
                    ["value" => "37", "text" => "Fish Spa Full Body Treatment"],
                    ["value" => "38", "text" => "Floatation"],
                    ["value" => "39", "text" => "Gua Sha"],
                    ["value" => "40", "text" => "Heat Treatments"],
                    ["value" => "41", "text" => "Hydrotherapy"],
                    ["value" => "42", "text" => "Hyperhidrosis Treatment"],
                    ["value" => "43", "text" => "i-Lipo"],
                    ["value" => "44", "text" => "Infrared Therapy"],
                    ["value" => "45", "text" => "Iyashi Dome"],
                    ["value" => "46", "text" => "Laser Lipo"],
                    ["value" => "47", "text" => "Leech Therapy"],
                    ["value" => "48", "text" => "Lipo-Light"],
                    ["value" => "49", "text" => "Lipodissolve"],
                    ["value" => "50", "text" => "No Needle Mesotherapy"],
                    ["value" => "51", "text" => "Photorejuvenation Treatments"],
                    ["value" => "52", "text" => "Piercing"],
                    ["value" => "53", "text" => "Pre-birth Acupuncture"],
                    ["value" => "54", "text" => "Rasul and Mud Treatments"],
                    ["value" => "55", "text" => "Russian Banya"],
                    ["value" => "56", "text" => "Scierotherapy"],
                    ["value" => "57", "text" => "SmartLipo"],
                    ["value" => "58", "text" => "Spray Tanning and Sunless Tanning"],
                    ["value" => "59", "text" => "Steam and Sauna Therapy"],
                    ["value" => "60", "text" => "Strawberry Laser Lipo"],
                    ["value" => "61", "text" => "Sun Angel"],
                    ["value" => "62", "text" => "Sunbeds and Tanning Booths"],
                    ["value" => "63", "text" => "Taizen Japanese Bath"],
                    ["value" => "64", "text" => "Tattooing"],
                    ["value" => "65", "text" => "Henna Designs"],
                    ["value" => "66", "text" => "Thalassotherapy"],
                    ["value" => "67", "text" => "Ultrasound Therapy"],
                    ["value" => "68", "text" => "Universal Contour Wrap"],
                    ["value" => "69", "text" => "Vaser Lipo-Contouring"],
                    ["value" => "70", "text" => "Vattooing"],
                    ["value" => "71", "text" => "VelaShape"],
                    ["value" => "72", "text" => "VinoTherapy"],
                    ["value" => "73", "text" => "Weight Loss Treatments"]
                ]
            ],
            [
                "label" => "Counselling & Holistic",
                "options" => [
                    ["value" => "74", "text" => "Past Life Regression Therapy"],
                    ["value" => "75", "text" => "Psychotherapy"],
                    ["value" => "76", "text" => "Weight Loss Hypnotherapy"],
                    ["value" => "77", "text" => "Traditional Chinese Medicine"],
                    ["value" => "78", "text" => "Timeline Therapy"],
                    ["value" => "79", "text" => "Thought Field Therapy"],
                    ["value" => "80", "text" => "Thermography"],
                    ["value" => "81", "text" => "The Lightning Process"],
                    ["value" => "82", "text" => "Styling"],
                    ["value" => "83", "text" => "Stress Management"],
                    ["value" => "84", "text" => "Speech Therapy"],
                    ["value" => "85", "text" => "Sophrology"],
                    ["value" => "86", "text" => "Somatic Experiencing"],
                    ["value" => "87", "text" => "Sleep Treatments"],
                    ["value" => "88", "text" => "Shirodhara"],
                    ["value" => "89", "text" => "Shamanic Healing"],
                    ["value" => "90", "text" => "Sex Counselling"],
                    ["value" => "91", "text" => "Reiki"],
                    ["value" => "92", "text" => "Radionics"],
                    ["value" => "93", "text" => "Psychology"],
                    ["value" => "94", "text" => "Psychic Love Coaching"],
                    ["value" => "95", "text" => "Acustaple"],
                    ["value" => "96", "text" => "Addictions Counselling"],
                    ["value" => "97", "text" => "Angel Therapy"],
                    ["value" => "98", "text" => "Anger Management"],
                    ["value" => "99", "text" => "Aromatherapy"],
                    ["value" => "100", "text" => "Ayurvedic"],
                    ["value" => "101", "text" => "Bach Flower Remedies"],
                    ["value" => "102", "text" => "BioMeridian Analysis"],
                    ["value" => "103", "text" => "Bioresonance Therapy"],
                    ["value" => "104", "text" => "BodyTalk"],
                    ["value" => "105", "text" => "Coaching"],
                    ["value" => "106", "text" => "Cognitive Behaviour Therapy"],
                    ["value" => "107", "text" => "Colour Therapy"],
                    ["value" => "108", "text" => "Combined Decongestive Therapy"],
                    ["value" => "109", "text" => "Counselling"],
                    ["value" => "110", "text" => "Crystal Therapy"],
                    ["value" => "111", "text" => "Doula Birth Companion"],
                    ["value" => "112", "text" => "Dream Therapy"],
                    ["value" => "113", "text" => "Ear Candling"],
                    ["value" => "114", "text" => "Emotional Therapy"],
                    ["value" => "115", "text" => "Energy Therapy"],
                    ["value" => "116", "text" => "Feng Shui"],
                    ["value" => "117", "text" => "Grief Recovery"],
                    ["value" => "118", "text" => "Halotherapy"],
                    ["value" => "119", "text" => "Healing"],
                    ["value" => "120", "text" => "Herbal & Flower Essence"],
                    ["value" => "121", "text" => "Herbal Medicine and Supplements"],
                    ["value" => "122", "text" => "Homeopathy"],
                    ["value" => "123", "text" => "HypnoBirthing"],
                    ["value" => "124", "text" => "Hypnotherapy"],
                    ["value" => "125", "text" => "Image Consulting"],
                    ["value" => "126", "text" => "Intuitive Readings"],
                    ["value" => "127", "text" => "Ionic Foot Bath"],
                    ["value" => "128", "text" => "Ionocinesis"],
                    ["value" => "129", "text" => "Iridology"],
                    ["value" => "130", "text" => "Ki Therapy"],
                    ["value" => "131", "text" => "Kinesiology"],
                    ["value" => "132", "text" => "Life Coaching"],
                    ["value" => "133", "text" => "Light Therapy"],
                    ["value" => "134", "text" => "Magnetic Therapy"],
                    ["value" => "135", "text" => "Meridian Therapies"],
                    ["value" => "136", "text" => "Metamorphic Technique"],
                    ["value" => "137", "text" => "Mind Boxing"],
                    ["value" => "138", "text" => "Mindfulness"],
                    ["value" => "139", "text" => "Moxibustion"],
                    ["value" => "140", "text" => "Naturopathy"],
                    ["value" => "141", "text" => "Neuro Linguistic Programming"],
                    ["value" => "142", "text" => "NeuroSpa"],
                    ["value" => "143", "text" => "Nutritional Advice & Treatments"]
                ]
            ],
            [
                "label" => "Face",
                "options" => [
                    ["value" => "144", "text" => "Brow Lift"],
                    ["value" => "145", "text" => "Chemical Skin Peel"],
                    ["value" => "146", "text" => "Dermaplaning"],
                    ["value" => "147", "text" => "Eye Treatments"],
                    ["value" => "148", "text" => "Eyebrow and Eyelash Tinting"],
                    ["value" => "149", "text" => "Eyebrow and Eyelash Treatments"],
                    ["value" => "150", "text" => "Eyelash Extensions"],
                    ["value" => "151", "text" => "Eyelash Perming"],
                    ["value" => "152", "text" => "Face Lift - Nonsurgical"],
                    ["value" => "153", "text" => "Facial Reflexology"],
                    ["value" => "154", "text" => "Facial Rejuvenation Acupuncture"],
                    ["value" => "155", "text" => "Facials"],
                    ["value" => "156", "text" => "Facials - CACI"],
                    ["value" => "157", "text" => "Facials - Galvanic"],
                    ["value" => "158", "text" => "Geisha Facial"],
                    ["value" => "159", "text" => "HD Brows"],
                    ["value" => "160", "text" => "Honey Facial"],
                    ["value" => "161", "text" => "LashDip"],
                    ["value" => "162", "text" => "Lava Shell Therma Facial"],
                    ["value" => "163", "text" => "LED Light Therapy"],
                    ["value" => "164", "text" => "LPG Facelift"],
                    ["value" => "165", "text" => "LVL Lashes"],
                    ["value" => "166", "text" => "Makeup Treatments"],
                    ["value" => "167", "text" => "Micro-Needling"],
                    ["value" => "168", "text" => "Microcurrent Treatments"],
                    ["value" => "169", "text" => "Microdermabrasion"],
                    ["value" => "170", "text" => "Oxygen Facial"],
                    ["value" => "171", "text" => "Placenta Facial"],
                    ["value" => "172", "text" => "Semi Permanent Mascara"],
                    ["value" => "173", "text" => "Acne Treatments"],
                    ["value" => "174", "text" => "Silk Peel"],
                    ["value" => "175", "text" => "Skincare Consultation"],
                    ["value" => "176", "text" => "Snail Facial"],
                    ["value" => "177", "text" => "Spermine Facial"],
                    ["value" => "178", "text" => "Stem Cell Facial"],
                    ["value" => "179", "text" => "Teen Facial"],
                    ["value" => "180", "text" => "Wedding Makeup"],
                    ["value" => "181", "text" => "Auricular Acupuncture"],
                    ["value" => "182", "text" => "Beauty Treatments"],
                    ["value" => "183", "text" => "Camouflage Make-up"]
                ]
            ],
            [
                "label" => "Hair Removal",
                "options" => [
                    ["value" => "184", "text" => "Depilation"],
                    ["value" => "185", "text" => "Ear Hair Trimming"],
                    ["value" => "186", "text" => "Epilar"],
                    ["value" => "187", "text" => "French Bikini Wax"],
                    ["value" => "188", "text" => "Hollywood Waxing"],
                    ["value" => "189", "text" => "Intense Pulsed Light Therapy (IPL)"],
                    ["value" => "190", "text" => "Laser Hair Removal"],
                    ["value" => "191", "text" => "Male Waxing"],
                    ["value" => "192", "text" => "Men's Shaving"],
                    ["value" => "193", "text" => "Nasal Hair Trimming"],
                    ["value" => "194", "text" => "Penazzling"],
                    ["value" => "195", "text" => "Shaving Lesson"],
                    ["value" => "196", "text" => "Soprano Laser Hair Removal"],
                    ["value" => "197", "text" => "Sugaring"],
                    ["value" => "198", "text" => "Threading"],
                    ["value" => "199", "text" => "Vajazzling"],
                    ["value" => "200", "text" => "Waxing"],
                    ["value" => "201", "text" => "Brazilian Waxing"],
                    ["value" => "202", "text" => "Electrolysis"],
                    ["value" => "203", "text" => "Beard Trimming"]
                ]
            ],
            [
                "label" => "Massage",
                "options" => [
                    ["value" => "204", "text" => "Tui Na Massage"],
                    ["value" => "205", "text" => "Honey Massage"],
                    ["value" => "206", "text" => "Himalayan Mineral Massage"],
                    ["value" => "207", "text" => "Herbal Compress Massage"],
                    ["value" => "208", "text" => "Hand Massage"],
                    ["value" => "209", "text" => "Garshan"],
                    ["value" => "210", "text" => "Four Hands Massage"],
                    ["value" => "211", "text" => "Foot Massage"],
                    ["value" => "212", "text" => "Face Massage"],
                    ["value" => "213", "text" => "Esalen Massage"],
                    ["value" => "214", "text" => "Deep Tissue Massage"],
                    ["value" => "215", "text" => "Children's Massage"],
                    ["value" => "216", "text" => "Chi Nei Tsang"],
                    ["value" => "217", "text" => "Chavutti Thirumal Massage"],
                    ["value" => "218", "text" => "Chakra Massage"],
                    ["value" => "219", "text" => "Chair Massage"],
                    ["value" => "220", "text" => "Biodynamic Massage"],
                    ["value" => "221", "text" => "Baobab Massage"],
                    ["value" => "222", "text" => "Bamboo Massage"],
                    ["value" => "223", "text" => "Ayurvedic Massages"],
                    ["value" => "224", "text" => "Ashiatsu"],
                    ["value" => "225", "text" => "Aromatherapy Massage"],
                    ["value" => "226", "text" => "Acupressure"],
                    ["value" => "227", "text" => "Abhyanga Ayurvedic Massage"],
                    ["value" => "228", "text" => "Reflexology"],
                    ["value" => "229", "text" => "Balinese Massage"],
                    ["value" => "230", "text" => "Moroccan Bath"],
                    ["value" => "231", "text" => "Wood Therapy"],
                    ["value" => "232", "text" => "Watsu Massage"],
                    ["value" => "233", "text" => "Vishesh Ayurvedic Massage"],
                    ["value" => "234", "text" => "Underwater Massage"],
                    ["value" => "235", "text" => "Turkish Bath"],
                    ["value" => "236", "text" => "Trigger Point Therapy"],
                    ["value" => "237", "text" => "Therapeutic Massage"],
                    ["value" => "238", "text" => "Thai Massage"],
                    ["value" => "239", "text" => "Thai Luk Pra Kob Massage"],
                    ["value" => "240", "text" => "Thai Foot Massage"],
                    ["value" => "241", "text" => "Swedish Massage"],
                    ["value" => "242", "text" => "Stone Massage Therapy"],
                    ["value" => "243", "text" => "Srota Ayrvendic Massage"],
                    ["value" => "244", "text" => "Sports Massage"],
                    ["value" => "245", "text" => "Sound Massage"],
                    ["value" => "246", "text" => "Six Hand Massage"],
                    ["value" => "247", "text" => "Shiatsu Massage"],
                    ["value" => "248", "text" => "Seitai Massage"],
                    ["value" => "249", "text" => "Scar Tissue Massage"],
                    ["value" => "250", "text" => "Rollerssage"],
                    ["value" => "251", "text" => "Pre and Post Natal Massage"],
                    ["value" => "252", "text" => "Platza"],
                    ["value" => "253", "text" => "No Hands Massage"],
                    ["value" => "254", "text" => "Neuromuscular Massage Therapy"],
                    ["value" => "255", "text" => "MELT Method"],
                    ["value" => "256", "text" => "Lymphatic Drainage Massage"],
                    ["value" => "257", "text" => "Lymphatic Drainage"],
                    ["value" => "258", "text" => "Lomi Lomi Massage"],
                    ["value" => "259", "text" => "Lava Shells Massage"],
                    ["value" => "260", "text" => "Lava Bambu Massage"],
                    ["value" => "261", "text" => "Khmer Massage"],
                    ["value" => "262", "text" => "Indian Head Massage"],
                    ["value" => "263", "text" => "Hydrotherm Massage"]
                ]
            ],
            [
                "label" => "Medical & Dental",
                "options" => [
                    ["value" => "264", "text" => "Drip therapy"],
                    ["value" => "265", "text" => "Allergy Testing"],
                    ["value" => "266", "text" => "Arm Lift"],
                    ["value" => "267", "text" => "Eye Tests"],
                    ["value" => "268", "text" => "Ear Pinning"],
                    ["value" => "269", "text" => "Dracula Therapy"],
                    ["value" => "270", "text" => "Dermatology"],
                    ["value" => "271", "text" => "Dermal Fillers"],
                    ["value" => "272", "text" => "Dental Treatments"],
                    ["value" => "273", "text" => "Cosmetic Surgery"],
                    ["value" => "274", "text" => "Cosmetic Skin Treatments"],
                    ["value" => "275", "text" => "Cosmetic Injectables"],
                    ["value" => "276", "text" => "Cosmetic Dental Treatments"],
                    ["value" => "277", "text" => "Contact Lenses"],
                    ["value" => "278", "text" => "Collagen Treatments"],
                    ["value" => "279", "text" => "Chicago Facelift"],
                    ["value" => "280", "text" => "Cheek Enhancement"],
                    ["value" => "281", "text" => "Carboxytherapy"],
                    ["value" => "282", "text" => "Buttock Implants"],
                    ["value" => "283", "text" => "Bust Treatments and Enhancement"],
                    ["value" => "284", "text" => "Breast Reduction"],
                    ["value" => "285", "text" => "Breast Fillers"],
                    ["value" => "286", "text" => "Breast Enlargement"],
                    ["value" => "287", "text" => "UVB Photo-Biological Stimulation"],
                    ["value" => "288", "text" => "Tummy Tuck"],
                    ["value" => "289", "text" => "TUG Breast Reconstruction"],
                    ["value" => "290", "text" => "Tooth Jewellery"],
                    ["value" => "291", "text" => "Thread Vein Treatment"],
                    ["value" => "292", "text" => "Thigh Lift"],
                    ["value" => "293", "text" => "Teeth Whitening"],
                    ["value" => "294", "text" => "Tattoo Removal"],
                    ["value" => "295", "text" => "Skin Tightening"],
                    ["value" => "296", "text" => "Skin Lightening"],
                    ["value" => "297", "text" => "Scar Tissue Treatments"],
                    ["value" => "298", "text" => "Scalp Reduction"],
                    ["value" => "299", "text" => "Rhinoplasty"],
                    ["value" => "230", "text" => "Orthodontics"],
                    ["value" => "231", "text" => "Necklift"],
                    ["value" => "232", "text" => "Natural Breast Enlargement"],
                    ["value" => "233", "text" => "Mole/Cyst Removal"],
                    ["value" => "234", "text" => "Blood Testing"],
                    ["value" => "235", "text" => "Biofeedback"],
                    ["value" => "236", "text" => "Areola Reconstruction"],
                    ["value" => "237", "text" => "Mole Removal"],
                    ["value" => "238", "text" => "Mesotherapy"],
                    ["value" => "239", "text" => "Mastopexy"],
                    ["value" => "240", "text" => "Liposuction"],
                    ["value" => "241", "text" => "Lipo-Injection"],
                    ["value" => "242", "text" => "Laser Treatments - Skin Rejuvenation"],
                    ["value" => "243", "text" => "Laser Treatments - Resurfacing"],
                    ["value" => "244", "text" => "Laser Treatment - Thread Veins"],
                    ["value" => "245", "text" => "Laser Eye Surgery"],
                    ["value" => "246", "text" => "Isologen Process"],
                    ["value" => "247", "text" => "Intraocular Lenses"],
                    ["value" => "248", "text" => "Implants (non Breast)"],
                    ["value" => "249", "text" => "Hormone Treatment"],
                    ["value" => "250", "text" => "Health Consultations"],
                    ["value" => "251", "text" => "Hair Simulation"],
                    ["value" => "252", "text" => "Glasses"],
                    ["value" => "253", "text" => "Gastric Band"],
                    ["value" => "254", "text" => "Fresh Breath Treatments"],
                    ["value" => "255", "text" => "Fertility Testing"],
                    ["value" => "256", "text" => "Face Lift"]
                ]
            ],
            [
                "label" => "Nails",
                "options" => [
                    ["value" => "257", "text" => "Fish Manicure"],
                    ["value" => "258", "text" => "Two Week Manicure"],
                    ["value" => "259", "text" => "Fish Analytics"],
                    ["value" => "260", "text" => "PerfectSense Paraffin Wax"],
                    ["value" => "261", "text" => "Pedicure"],
                    ["value" => "262", "text" => "Paraffin Wax Treatments"],
                    ["value" => "263", "text" => "Nail Extensions and Overlays"],
                    ["value" => "264", "text" => "Callus Peel"],
                    ["value" => "265", "text" => "Swarovski Crystal Pedicure"],
                    ["value" => "266", "text" => "Snakeskin Manicure"],
                    ["value" => "267", "text" => "Nail Art"],
                    ["value" => "268", "text" => "Minx Nails"],
                    ["value" => "269", "text" => "Manicure"],
                    ["value" => "270", "text" => "Gel Nails"],
                    ["value" => "271", "text" => "Foot Scrub"],
                    ["value" => "272", "text" => "Fish Pedicure"]
                ]
            ],
            [
                "label" => "Physical Therapy",
                "options" => [
                    ["value" => "273", "text" => "Yumuna Body Rolling"],
                    ["value" => "274", "text" => "Trager Approach"],
                    ["value" => "275", "text" => "The Rossiter System"],
                    ["value" => "276", "text" => "The Emmett Technique"],
                    ["value" => "277", "text" => "SIRPA Recovery Programme"],
                    ["value" => "278", "text" => "Rolfing"],
                    ["value" => "279", "text" => "Resistance Stretching"],
                    ["value" => "290", "text" => "Physiotherapy"],
                    ["value" => "291", "text" => "Passive Exercise"],
                    ["value" => "292", "text" => "Osteopathy"],
                    ["value" => "293", "text" => "Neuro-skeletal Realignment Therapy"],
                    ["value" => "294", "text" => "Naprapathy"],
                    ["value" => "295", "text" => "Myofascial Release Therapy"],
                    ["value" => "296", "text" => "Hippotherapy"],
                    ["value" => "297", "text" => "Hallerwork"],
                    ["value" => "298", "text" => "Grinberg Method"],
                    ["value" => "299", "text" => "Feldenkrais Method"],
                    ["value" => "300", "text" => "Dorn Method"],
                    ["value" => "301", "text" => "Craniosacral Therapy"],
                    ["value" => "302", "text" => "Arvigo Therapy"],
                    ["value" => "303", "text" => "Amatsu"],
                    ["value" => "304", "text" => "Alexander Technique"],
                    ["value" => "305", "text" => "Bowen Technique"],
                    ["value" => "306", "text" => "Chiropody"],
                    ["value" => "307", "text" => "Chiropractic"]
                ]
            ]
        ];

        if (empty($value)) {
            return $treatment_types;
        }

        foreach ($treatment_types as $type) {
            foreach ($type["options"] as $option) {
                if ($option["value"] == $value) {
                    return $option;
                }
            }
        }

        return [];
    }

    public static function getDealsFilteredTreatments($request = null)
    {
        $treatmentTypes = Helpers::getTreatmentTypes();
        $treatmentTypes = collect($treatmentTypes);
            $services = [];

            $deals = Deals::with(['inclusions' => function($query) use ($request){
                $query->with('service')->whereHas('service');
            }])->whereDate('available_from','<=',Carbon::now()->toDateTimeString())
            ->whereDate('available_until','>=',Carbon::now()->toDateTimeString())
            ->whereIsActive(1)->get();
            foreach($deals as $deal){
                foreach($deal->inclusions as $inclusion){
                    if(!empty($inclusion->service)){
                        array_push($services,$inclusion->service);
                    }
                }

            }

            $treatmentTypes = $treatmentTypes->map(function($filter) use ($services){
                $filter["showSubMenu"] = false;
                $filter["options"] = collect($filter["options"])->map(function($option) use ($services){
                    foreach($services as $service){
                        if(!empty($service) && $option["value"] == $service->treatment_type){
                            return $option;
                        }
                    }
                })->filter(function($res){
                    if(!empty($res)){
                        return $res;
                    };
                })->values();
                return $filter;
            })->filter(function($res){
                if(count($res["options"]) > 0){
                    return $res;
                };
            })->values();            

            return $treatmentTypes;
    }

    public static function getBusinessTypes($icons = [])
    {
        $businessTypes = [
            [ "title" => "Hair Salon", "icon" => "hair-salon", "url" => config("app.url") . "images/business_types/hair-salon.png" ],
            [ "title" => "Nail Salon", "icon" => "nail-salon", "url" => config("app.url") . "images/business_types/nail-salon.png" ],
            [ "title" => "Barbershop", "icon" => "barbershop", "url" => config("app.url") . "images/business_types/barbershop.png" ],
            [ "title" => "Beauty Salon", "icon" => "beauty-salon", "url" => config("app.url") . "images/business_types/beauty-salon.png" ],
            [ "title" => "Spa", "icon" => "spa", "url" => config("app.url") . "images/business_types/spa.png" ],
            [ "title" => "Waxing Salon", "icon" => "waxing-salon", "url" => config("app.url") . "images/business_types/waxing-salon.png" ],
            [ "title" => "Personal Trainer", "icon" => "personal-trainer", "url" => config("app.url") . "images/business_types/personal-trainer.png" ],
            [ "title" => "Eyebrows & Lashes", "icon" => "eyebrow-lashes", "url" => config("app.url") . "images/business_types/eyebrow-lashes.png" ],
            [ "title" => "Gym & Fitness", "icon" => "gym-fitness", "url" => config("app.url") . "images/business_types/gym-fitness.png" ],
            [ "title" => "Therapy Center", "icon" => "therapy-center", "url" => config("app.url") . "images/business_types/therapy-center.png" ]
        ];

        if (empty($icons)) {
            return $businessTypes;
        }

        $filteredBusinessTypes = [];

        foreach ($businessTypes as $typeRow) {
            $iconNameWithoutExtension = pathinfo($typeRow["icon"], PATHINFO_FILENAME);
            
            if (in_array($iconNameWithoutExtension, $icons)) {
                $filteredBusinessTypes[] = $typeRow;
            }
        }

        return $filteredBusinessTypes;
        
    }

    public static function getIncreaseStockReasons($value = null)
    {
        $array = [
            ["value" => "new_stock", "text" => "New Stock"],
            ["value" => "return", "text" => "Return"],
            ["value" => "transfer", "text" => "Transfer"],
            ["value" => "adjustment", "text" => "Adjustment"],
            ["value" => "other", "text" => "Other"]
        ];

        if (!$value) {
            return $array;
        }

        return collect($array)->firstWhere("value", $value)["text"];
    }

    public static function getDecreaseStockReasons($value = null)
    {
        $array = [
            ["value" => "internal_use", "text" => "Internal use"],
            ["value" => "damaged", "text" => "Damaged"],
            ["value" => "out_of_date", "text" => "Out of date"],
            ["value" => "adjustment", "text" => "Adjustment"],
            ["value" => "lost", "text" => "Lost"],
            ["value" => "other", "text" => "Other"]
        ];

        if (!$value) {
            return $array;
        }

        return collect($array)->firstWhere("value", $value)["text"];
    }

    public static function getAppointmentCancellationReasons($value = null)
    {
        $array = [
            ["value" => 1, "text" => "Appointment made by mistake"],
            ["value" => 2, "text" => "Cancelled via SMS"],
            ["value" => 3, "text" => "Change of mind"],
            ["value" => 4, "text" => "Cancelled via void invoice"]
        ];

        if (!$value) {
            return $array;
        }

        return collect($array)->firstWhere("value", $value)["text"];
    }

    public static function getColorList()
    {
        return [
            "#f795af", "#e2a6e6", "#bbc1e8", "#a5dff8", "#91e3ee",
            "#6cd5cb", "#a6e5bd", "#e7f286", "#ffec78", "#ffbf69"
        ];
    }

    public static function createDirectoryAndUploadMedia($folderPath, $image, $fileName = null)
    {
        if (strpos($image, ";base64,") !== false) {
            $imageParts = explode(";base64,", $image);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            $imageBase64 = base64_decode($imageParts[1]);
            File::makeDirectory(base_path("public/uploads/" . $folderPath), 0777, true, true);
            $fileName = ($fileName ?? Str::random(10));
            self::deleteFile($folderPath, $fileName);
            $file = base_path("public/uploads/" . $folderPath . '/') . $fileName . '.' . $imageType;
            File::put($file, $imageBase64);
        }
        
        return true;
    }

    public static function deleteFile($folderPath, $fileName, $imageType = "jpeg")
    {
        $file = base_path("public/uploads/" . $folderPath . '/') . $fileName . '.' . $imageType;
        File::delete($file);
    }

    public static function getExpensesCategories($value = null)
    {
        $array = [
            ["value" => "rent-mortgage-payments", "text" => "Rent or mortgage payments"],
            ["value" => "home-office-costs", "text" => "Home office costs"],
            ["value" => "utilities", "text" => "Utilities"],
            ["value" => "furniture-equipment-machinery", "text" => "Furniture, equipment, and machinery"],
            ["value" => "office-supplies", "text" => "Office supplies"],
            ["value" => "advertising-and-marketing", "text" => "Advertising and marketing"],
            ["value" => "website-and-software-expenses", "text" => "Website and software expenses"],
            ["value" => "entertainment", "text" => "Entertainment"],
            ["value" => "business-meals-and-travel-expenses", "text" => "Business meals and travel expenses"],
            ["value" => "vehicle-expenses", "text" => "Vehicle expenses"],
            ["value" => "payroll", "text" => "Payroll"],
            ["value" => "employee-benefits", "text" => "Employee benefits"],
            ["value" => "taxes", "text" => "Taxes"],
            ["value" => "business-insurance", "text" => "Business insurance"],
            ["value" => "business-licenses-and-permits", "text" => "Business licenses and permits"],
            ["value" => "interest-payments-and-bank-fees", "text" => "Interest payments and bank fees"],
            ["value" => "membership-fees", "text" => "Membership fees"],
            ["value" => "professional-fees-and-business-services", "text" => "Professional fees and business services"],
            ["value" => "training-and-education", "text" => "Training and education"]
        ];

        if (!$value) {
            return $array;
        }

        return collect($array)->firstWhere("value", $value)["text"];
    }

    public static function getTimeFormat($business)
    {
        $format = " g:i A";
            
        if ($business && $business->time_format === "24h") {
            $format = " H:i:s";
        }

        return $format;
    }

    public static function getSMSPackages($country_id = null)
    {
        $country_id = $country_id ?? (request()->has("country_id") ? request()->country_id : null);
        $array = [];

        if ($country_id == 1) {
            $array = [
                ["title" => "1000 SMS", "text" => "Rs. 1100 + Tax", "sms_credits" => 1000, "order_reference" => "18838913230000", "price" => 1000 * ((100 + 17.5) / 100)],
                ["title" => "5000 SMS", "text" => "Rs. 5000 + Tax", "sms_credits" => 5000, "order_reference" => "18838913240000", "price" => 5000 * ((100 + 17.5) / 100)],
                ["title" => "10000 SMS", "text" => "Rs. 9500 + Tax", "sms_credits" => 10000, "order_reference" => "18838913250000", "price" => 9500 * ((100 + 17.5) / 100)]
            ];
        }

        return $array;
    }

    public static function getSubscriptionPackages($country_id = null)
    {
        $country_id = $country_id ?? (request()->has("country_id") ? request()->country_id : null);
        $array = [
            ["title" => "Standard Plan", "price" => 3000, "order_reference" => "18838913231111", "options" => [
                ["is_available" => true, "title" => "Unlimited branches"],
                ["is_available" => true, "title" => "Business page on servu.app"],
                ["is_available" => true, "title" => "Online Booking (ServU, Facebook, Instagram)"],
                ["is_available" => false, "title" => "Sell Deals (ServU, Facebook, Instagram)"],
                ["is_available" => true, "title" => "SMS Reminders (Paid Option)"],
                ["is_available" => false, "title" => "SMS Marketing"],
                ["is_available" => true, "title" => "24x7 Email Support"],
                ["is_available" => true, "title" => "Online Payment Processing Charge (9.5%)"]
            ]],
            ["title" => "Premium Plan", "price" => 5000, "order_reference" => "18838913241111", "options" => [
                ["is_available" => true, "title" => "Unlimited branches"],
                ["is_available" => true, "title" => "Business page on servu.app"],
                ["is_available" => true, "title" => "Online Booking (ServU, Facebook, Instagram)"],
                ["is_available" => true, "title" => "Sell Deals (ServU, Facebook, Instagram)"],
                ["is_available" => true, "title" => "SMS Reminders (Paid Option)"],
                ["is_available" => true, "title" => "SMS Marketing"],
                ["is_available" => true, "title" => "24x7 Email Support"],
                ["is_available" => true, "title" => "Online Payment Processing Charge (9.5%)"]
            ]]
        ];

        return $array;
    }

    public static function getStaffRoles()
    {
        return [
            [ "value" => null, "text" => "No Accesss" ],
            [ "value" => "basic", "text" => "Basic" ],
            [ "value" => "low", "text" => "Low" ],
            [ "value" => "medium", "text" => "Medium" ],
            [ "value" => "high", "text" => "High" ]
        ];
    }

    public static function getMaskedPhoneNumber($phoneNumber, $maskedHashes, $charToConvert = '#')
    {
        $y = 0;
        for ($i = 0; $i < strlen($maskedHashes); $i++) {
            if ($maskedHashes[$i] == $charToConvert) {
                $maskedHashes[$i] = $phoneNumber[$y];
                $y++;
            }
        }

        return $maskedHashes;
    }
}