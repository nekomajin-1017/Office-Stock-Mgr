@props([
    'model',
    'entityLabel',
])

<x-form-field name="code" :label="$entityLabel.'コード'" :value="$model->code" required autofocus />
<x-form-field name="name" :label="$entityLabel.'名'" :value="$model->name" required />
<x-form-field name="postal_code" label="郵便番号" :value="$model->postal_code" />
<x-form-field name="address" label="住所" :value="$model->address" />
<x-form-field name="phone" label="電話番号" :value="$model->phone" />
<x-form-field name="email" type="email" label="メールアドレス" :value="$model->email" />
<x-form-field name="contact_person" label="担当者名" :value="$model->contact_person" />

<div class="content-block form-group">
    <label class="field-label form-label" for="is-active">状態</label>
    <select id="is-active" class="form-element form-control" name="is_active" required>
        <option value="1" @selected(old('is_active', $model->exists ? (int) $model->is_active : 1) == 1)>有効</option>
        <option value="0" @selected(old('is_active', $model->exists ? (int) $model->is_active : 1) == 0)>無効</option>
    </select>
    @error('is_active')
        <p class="text-content field-error">{{ $message }}</p>
    @enderror
</div>
