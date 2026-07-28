@extends('layouts.app')

@section('title', '仕入伝票登録')

@section('content')
  @php($items = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]))
  <section class="dashboard-main form-page">
    <h1>仕入伝票登録</h1>
    <form class="purchase-form" action="{{ route('purchases.store') }}" method="post" data-purchase-form>
      @csrf
      <div class="form-group"><label class="form-label" for="supplier-id">仕入先</label><select id="supplier-id" class="form-control" name="supplier_id" required><option value="">選択してください</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) $supplier->id === old('supplier_id'))>{{ $supplier->name }}</option>@endforeach</select>@error('supplier_id')<p class="field-error">{{ $message }}</p>@enderror</div>
      <x-form-field name="purchase_date" type="date" label="仕入日" :value="old('purchase_date', now()->toDateString())" required />
      <section aria-labelledby="purchase-items-title"><h2 id="purchase-items-title">明細</h2><div data-purchase-items>
        @foreach($items as $index => $item)<div class="purchase-item" data-purchase-item><select class="form-control" name="items[{{ $index }}][product_id]" required><option value="">商品を選択</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) $product->id === (string) ($item['product_id'] ?? ''))>{{ $product->code }} / {{ $product->name }}</option>@endforeach</select><input class="form-control" name="items[{{ $index }}][quantity]" type="number" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" required><input class="form-control" name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}" required><output data-line-total>0.00 円</output><button class="action-button action-button-danger" type="button" data-remove-item>削除</button></div>@endforeach
      </div><button class="action-button" type="button" data-add-item>明細を追加</button><p>伝票合計: <output data-purchase-total>0.00 円</output></p></section>
      <div class="form-actions"><a href="{{ route('purchases.index') }}">一覧へ戻る</a><button class="button button-inline" type="submit">下書き登録</button></div>
    </form>
  </section>
  <template data-purchase-template><div class="purchase-item" data-purchase-item><select class="form-control" name="items[__INDEX__][product_id]" required><option value="">商品を選択</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->code }} / {{ $product->name }}</option>@endforeach</select><input class="form-control" name="items[__INDEX__][quantity]" type="number" min="1" step="1" value="1" required><input class="form-control" name="items[__INDEX__][unit_price]" type="number" min="0" step="0.01" required><output data-line-total>0.00 円</output><button class="action-button action-button-danger" type="button" data-remove-item>削除</button></div></template>
@endsection
