<table>
    <thead>
        <tr>
            <td height="42px" valign="center" style="font-weight:bold;" colspan="4">Foglio firme briefing/Briefing participation module</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td height="42px" valign="center" style="font-weight:bold;" colspan="4">{{ $race->title }}</td>
        </tr>
        <tr>
            <td height="31px" valign="center" style="" colspan="4">{{ $race->period }} - {{ $race->track }}</td>
        </tr>

        <tr>
            <td bgcolor="#DDDDDD">No./Race number</td>
            <td bgcolor="#DDDDDD" width="300px">Conduttore/Driver</td>
            <td bgcolor="#DDDDDD" width="160px">Categoria/Category</td>
            <td bgcolor="#DDDDDD" width="300px">Firma/Signature</td>
        </tr>
        @foreach ($participants as $participant)
            <tr>
                <td height="42px" valign="center" style="font-size:12;border-bottom:1px solid #bbbbbb">{{ $participant->bib }}</td>
                <td height="42px" valign="center" style="font-size:12;border-bottom:1px solid #bbbbbb">{{ str("{$participant->first_name} {$participant->last_name}")->title() }}</td>
                <td height="42px" valign="center" style="font-size:12;border-bottom:1px solid #bbbbbb">{{ $participant->racingCategory?->name }}</td>
                <td height="42px" valign="center" style="font-size:12;border-bottom:1px solid #bbbbbb">&nbsp;</td>
            </tr>
            
        @endforeach

    
    </tbody>
</table>