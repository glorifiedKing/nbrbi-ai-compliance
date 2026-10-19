import sys
sys.path.insert(0, 'E:/nbrbi-ai-fastapi')
import json
from google import genai
from google.genai import types
from app.config import settings

client = genai.Client(api_key=settings.GEMINI_API_KEY)

prompt = """
You are an expert Chief Building Control Officer in Uganda auditing a drawing.
Return JSON strictly conforming to this structure:
{
  "overall_status": "PASS",
  "summary_metrics": {
    "pass_count": 5,
    "fail_count": 0,
    "warning_count": 0
  },
  "discrepancies": [],
  "floor_area_metrics": {
    "is_floor_plan": true,
    "gross_floor_area_sqm": 722.0,
    "net_internal_area_sqm": 635.0,
    "confidence_score": 0.95,
    "source": "title_block_schedule",
    "room_breakdown": []
  }
}
"""

models_to_try = ['gemini-3.5-flash', 'gemini-3.8-flash', 'gemini-flash-latest']
success = False

for m in models_to_try:
    try:
        resp = client.models.generate_content(
            model=m,
            contents=prompt,
            config=types.GenerateContentConfig(
                response_mime_type="application/json",
                temperature=0.1
            )
        )
        print(f"{m} SUCCESS!")
        parsed = json.loads(resp.text)
        print("overall_status:", parsed.get("overall_status"))
        print("summary_metrics:", parsed.get("summary_metrics"))
        print("floor_area_metrics:", parsed.get("floor_area_metrics"))
        success = True
        break
    except Exception as e:
        print(f"{m} error: {e}")

if not success:
    print("All models failed.")
