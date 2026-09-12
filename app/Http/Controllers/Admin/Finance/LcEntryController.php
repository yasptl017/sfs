<?php
namespace App\Http\Controllers\Admin\Finance;
use App\Http\Controllers\Controller;use App\Models\{BudgetCode,LcEntry};use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class LcEntryController extends Controller {
 public function index(Request $r):View{$this->auth($r);return view('admin.finance.lc-entries.index',['entries'=>LcEntry::where('division_id',$r->user()->id)->latest('serial_number')->paginate(10)]);}
 public function create(Request $r):View{$this->auth($r);return view('admin.finance.lc-entries.form',$this->formData($r));}
 public function store(Request $r):RedirectResponse{$this->auth($r);LcEntry::create(array_merge($this->valid($r),['division_id'=>$r->user()->id,'serial_number'=>$this->next($r)]));return redirect()->route('division.lc-entries.index')->with('status','L. C. entry created successfully.');}
 public function edit(Request $r,LcEntry $lcEntry):View{$this->owner($r,$lcEntry);return view('admin.finance.lc-entries.form',array_merge($this->formData($r),['entry'=>$lcEntry]));}
 public function update(Request $r,LcEntry $lcEntry):RedirectResponse{$this->owner($r,$lcEntry);$lcEntry->update($this->valid($r));return redirect()->route('division.lc-entries.index')->with('status','L. C. entry updated successfully.');}
 public function destroy(Request $r,LcEntry $lcEntry):RedirectResponse{$this->owner($r,$lcEntry);$lcEntry->delete();return back()->with('status','L. C. entry deleted successfully.');}
 private function formData(Request $r):array{return ['entry'=>null,'next'=>$this->next($r),'schemes'=>BudgetCode::whereNotNull('scheme')->where('scheme','!=','')->distinct()->orderBy('scheme')->pluck('scheme'),'classes'=>BudgetCode::whereNotNull('object_class')->where('object_class','!=','')->distinct()->orderBy('object_class')->pluck('object_class')];}
 private function valid(Request $r):array{return $r->validate(['scheme'=>['required','string','max:255'],'class'=>['required','string','max:255'],'entry_date'=>['required','date'],'amount'=>['required','numeric','min:0']]);}
 private function next(Request $r):int{return ((int)LcEntry::where('division_id',$r->user()->id)->max('serial_number'))+1;}private function auth(Request $r):void{abort_unless($r->user()->isDivision(),403);}private function owner(Request $r,LcEntry $e):void{$this->auth($r);abort_unless($e->division_id===$r->user()->id,403);}
}
