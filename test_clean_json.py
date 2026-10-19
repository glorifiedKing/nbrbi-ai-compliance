import json
import re
from typing import Optional, Dict, Any, List
from pydantic import BaseModel
from enum import Enum

class OverallStatusEnum(str, Enum):
    PASS = "PASS"
    WARNING = "WARNING"
    FAIL = "FAIL"
    REJECTED_PREFLIGHT = "REJECTED_PREFLIGHT"

class SummaryMetricsSchema(BaseModel):
    pass_count: int = 0
    fail_count: int = 0
    warning_count: int = 0

class DiscrepancyItem(BaseModel):
    element_id: str
    room_or_grid: str
    clause_reference: str
    required_value: str
    observed_value: str
    severity: str
    recommendation: str

class RoomAreaItem(BaseModel):
    name: str
    area_sqm: float

class FloorAreaMetrics(BaseModel):
    is_floor_plan: bool = False
    gross_floor_area_sqm: Optional[float] = None
    net_internal_area_sqm: Optional[float] = None
    confidence_score: float = 0.90
    source: Optional[str] = "title_block_schedule"
    room_breakdown: List[RoomAreaItem] = []

class GeminiAuditSchema(BaseModel):
    overall_status: OverallStatusEnum
    summary_metrics: SummaryMetricsSchema
    discrepancies: List[DiscrepancyItem] = []
    floor_area_metrics: Optional[FloorAreaMetrics] = None

def clean_json_response(text: str) -> dict:
    cleaned = text.strip()
    if cleaned.startswith("```json"):
        cleaned = cleaned[7:]
    elif cleaned.startswith("```"):
        cleaned = cleaned[3:]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]
    return json.loads(cleaned.strip())

sample_gemini_raw = """```json
{
  "overall_status": "PASS",
  "summary_metrics": {
    "pass_count": 6,
    "fail_count": 0,
    "warning_count": 0
  },
  "discrepancies": [],
  "floor_area_metrics": {
    "is_floor_plan": true,
    "gross_floor_area_sqm": 722.0,
    "net_internal_area_sqm": 635.36,
    "confidence_score": 0.95,
    "source": "title_block_schedule",
    "room_breakdown": []
  }
}
```"""

parsed = clean_json_response(sample_gemini_raw)
print("Parsed overall_status:", parsed["overall_status"])
print("Parsed gross_floor_area_sqm:", parsed["floor_area_metrics"]["gross_floor_area_sqm"])
